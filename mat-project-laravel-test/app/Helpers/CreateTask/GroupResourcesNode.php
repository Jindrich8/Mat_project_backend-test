<?php

namespace App\Helpers\CreateTask {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\CreatableNodeTrait;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeValueType;

    class GroupResourcesNode extends XMLNodeBaseWParentNode
    {
        use XMLNoValueNodeTrait;

        public static function create(GroupNode $parent){
            $node = new self($parent);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild(ResourcesResourceNode::create($node),required:true)
            );
            return $node;
        }

        private function __construct(GroupNode $group){
            parent::__construct(
                parent:$group,
            name:TaskSrcConfig::get()->groupResourcesName
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