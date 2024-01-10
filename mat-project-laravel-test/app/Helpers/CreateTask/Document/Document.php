<?php

namespace App\Helpers\CreateTask\Document {

    use App\Exceptions\XMLInvalidElementException;
    use App\Helpers\CreateTask\ExerciseNode;
    use App\Helpers\CreateTask\TaskRes;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\Helpers\CreateTask\XMLOneUseNode;
    use App\Models\Task;
    use App\MyConfigs\TaskSrcConfig;
    use App\TableSpecificData\TaskDisplay;
    use App\Types\XMLAttributes;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeValueType;
    use Illuminate\Support\Str;

    class Document extends XMLNodeBaseWParentNode
    {
        use XMLNoValueNodeTrait;

        public static function create():Document{
            $doc = new self();
            $docDesc = DocumentDescription::create($doc);
            if($docDesc->getParentObjectId() === null){
                dump("DOCUMENT DESCRIPTION DOES NOT HAVE PARENT!!!");
            }
            $doc->setChildren(
                XMLChildren::construct()
            ->addChild($docDesc,required:true)
            ->addChild(DocumentContent::create($doc),required:true)
        );
        return $doc;
        }

       private function __construct()
       {
        $config = TaskSrcConfig::get();
        parent::__construct(
            parent:null,
            name:$config->taskName,
            maxCount:1,
            attributes:XMLAttributes::construct()->addAttribute(
                name:$config->taskNameAttr->name,
                required:true,
                parse:function(XMLNodeBase $node,string $attributeValue,XMLContextBase $context) {
                    $attr = TaskSrcConfig::get()->taskNameAttr;
                    if($error =$attr->validate($attributeValue)){
                        $node->invalidAttributeValue($attr->name,$error,$context);
                    }
                $context->getTaskRes()->task->name = $attributeValue;
            })->addAttribute(
                name:$config->taskOrientationAttr->name,
                required:true,
                parse:function(XMLNodeBase $node,string $attributeValue,XMLContextBase $context)use($config) {
                    $attr = TaskSrcConfig::get()->taskOrientationAttr;
                   $orientation = $attr->validate($attributeValue);
                   if(!$orientation){
                      $node->invalidEnumAttributeValue(
                        $attr->name,
                        $attr->getAllowedEnumStringValues(),
                      $context
                  );
              }
                  $context->getTaskRes()->task->orientation = $orientation->value;
          }
        )
        );
       }

       function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
       {
        $context->getTaskRes()->task = new Task();
        parent::validateStart($attributes,$context,$name);
       }
        
    }
}