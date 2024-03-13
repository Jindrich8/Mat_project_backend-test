<?php

namespace App\Helpers\CreateTask\Document {

    use App\Types\XML\XMLNodeBaseWParentNode;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\TaskResTask;
    use App\Types\XML\XMLChildren;
    use App\Types\XML\XMLContextBase;

    class Document extends XMLNodeBaseWParentNode
    {

        public static function create():Document{
            $doc = new self();
            $docDesc = DocumentDescription::create($doc);
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
            isValueNode:false
        );
       }

       function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
       {
        $context->getTaskRes()->task = new TaskResTask();
        parent::validateStart($attributes,$context,$name);
       }

    }
}
