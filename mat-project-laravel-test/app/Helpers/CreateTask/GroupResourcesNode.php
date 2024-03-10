<?php

namespace App\Helpers\CreateTask {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XML\XMLNodeBaseWParentNode;
    use App\Types\XML\XMLChildren;
    use App\Types\XML\XMLContextBase;

    class GroupResourcesNode extends XMLNodeBaseWParentNode
    {

        public static function create(GroupNode $parent): GroupResourcesNode
        {
            $node = new self($parent);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild(ResourcesResourceNode::create($node),required:true)
            );
            return $node;
        }

        private function __construct(GroupNode $group){
            parent::__construct(
                name: TaskSrcConfig::get()->groupResourcesName,
                parent: $group,
                isValueNode:false
            );
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            // dump("GroupResourcesNode VALIATE START");
            parent::validateStart($attributes,$context,$name);
            $taskRes = $context->getTaskRes();
            if ($taskRes->getNumOfResourcesInCurrentGroup() > 0) {
                $this->tooManyElements(
                    getPosCallback: $context,
                    maximum: 1
            );
            }
        }
}
}
