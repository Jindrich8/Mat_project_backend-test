<?php

namespace App\Helpers\CreateTask\Document {

    use App\Helpers\CreateTask\ExerciseNode;
    use App\Helpers\CreateTask\GroupNode;
    use App\Types\XMLNodeBaseWParentNode;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLChildren;

    class DocumentContent extends XMLNodeBaseWParentNode{

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
            shouldHaveAtLeastOneChild:true,
            isValueNode:false
        );
        }
}
}
