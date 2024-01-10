<?php

namespace App\Helpers\CreateTask\Document {

    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNoAttributesNode;
    use App\Helpers\CreateTask\Document\Document;
    use App\Helpers\CreateTask\ExerciseNode;
    use App\Helpers\CreateTask\GroupNode;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeValueType;
    use App\Utils\Utils;

    class DocumentContent extends XMLNodeBaseWParentNode{
        use XMLNoValueNodeTrait;

        public static function create(Document $document){
            $content = new self($document);
            $content->setChildren(
                XMLChildren::construct()
                ->addChild(ExerciseNode::create($content))
                ->addChild(GroupNode::create($content))
            );
            return $content;
        }

        private function __construct(Document $document){
            parent::__construct(parent:$document,
            name:TaskSrcConfig::get()->taskContentName,
            maxCount:1,
            shouldHaveAtLeastOneChild:true
        );
        }
}
}