<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\Helpers\CreateTask\Document\Document;
    use App\Helpers\CreateTask\Document\DocumentContent;
    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Helpers\CreateTask\XMLOneUseNode;
    use App\Helpers\ExerciseType;
    use App\Models\Exercise;
    use App\MyConfigs\TaskSrcConfig;
    use App\TableSpecificData\TaskDisplay;
    use App\Types\XMLAttributes;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Utils\Utils;
    use App\Utils\ValidateUtils;
    use Illuminate\Support\Str;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\XMLNodeBase;
    use App\Helpers\CreateTask\ExerciseInstructionsNode;
    use App\Types\XMLNodeValueType;

    class ExerciseNode extends XMLNodeBaseWParentNode
    {
        use XMLNoValueNodeTrait;

        public static function create(DocumentContent|GroupMembersNode $parent){
            $node = new self($parent);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild(ExerciseInstructionsNode::create($node),required:true)
                ->addChild(ExerciseContentNode::create($node),required:true)
            );
            return $node;
        }

        private function __construct(DocumentContent|GroupMembersNode $parent){
            $config = TaskSrcConfig::get();
            parent::__construct(
                name:$config->exerciseName,
            parent:$parent,
            attributes:XMLAttributes::construct()
            ->addAttribute(
                name:$config->exerciseTypeAttr->name,
                required:true,
            parse:function(XMLNodeBase $node,string $value,XMLContextBase $context){
                $attr = TaskSrcConfig::get()->exerciseTypeAttr;
               $type = $attr->validate($value);
               if($type === null){
                $this->invalidEnumAttributeValue(
                    attribute:$attr->name,
                    allowedValues:$attr->getAllowedEnumStringValues(),
                    getPosCallback:$context
                );
               }
               $context->getTaskRes()->getLastExercise()->exerciseable_type = $type->value;
            })
            ->addAttribute(
                name:$config->exerciseWeightAttr->name,
                required:true,
                parse:function(XMLNodeBase $node,string $value,XMLContextBase $context){
                    $attr = TaskSrcConfig::get()->exerciseWeightAttr;
                   $error = $attr->validate($value,$parsed);
                   if($error){
                    $this->invalidAttributeValue(
                        attribute:$attr->name,
                    description:$error,
                    getPosCallback:$context
                    );
                   }
                   $context->getTaskRes()->getLastExercise()->weight = $parsed;
                }
            )
        );
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            $taskRes = $context->getTaskRes();
            $maxExerciseCount = TaskSrcConfig::get()->maxExerciseCount;
            $specified = $taskRes->getExerciseCount();
            if($specified >= $maxExerciseCount){
                $this->tooManyElements($context,$maxExerciseCount);
            }
            $taskRes->addExercise();
            
            parent::validateStart($attributes,$context,$name);
        }
    }
}