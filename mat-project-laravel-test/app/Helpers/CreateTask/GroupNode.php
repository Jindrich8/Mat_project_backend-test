<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\Helpers\CreateTask\Document\DocumentContent;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\XMLNodeBase;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Utils\Utils;

    class GroupNode extends XMLNodeBase{
        use XMLNoValueNodeTrait;

        private XMLNodeBase $parent;
        private ?GroupMembersNode $members;
        private ?GroupResourcesNode $resources;

        /**
         * @var int[] $indexes
         */
        private array $indexes;

        public static function create(DocumentContent $parent){
            $node = new self($parent);


            $members = GroupMembersNode::create($node);
            $node->members = $members;
            $resources = GroupResourcesNode::create($node);
            $node->resources = $resources;
            // $node->members = $members;
            // array_push($node->indexes,[1]);
            // ($members->children ??= new XMLChildren())->addChildWithPossiblyDifferentParent($node);
            // $node->indexes = [];


            $node->setChildren(
                XMLChildren::construct()
                ->addChild($resources)
                ->addChild($members)
            );

            return $node;
        }

        private function __construct(DocumentContent $parent)
        {
            $config = TaskSrcConfig::get();
            parent::__construct(
                name:$config->groupName,
            );
            $this->parent = $parent;
                $this->indexes = [];
                $this->members = null;
        }

        private function getGroupMembers(){
           $members = $this->members;
           if(!$members){
            throw new InternalException(
                "GroupNode should have a group members node as child!",
            context:['groupNode'=>$this]
        );
           }
           return $members;
        }

        private function getGroupResources(){
            $resources = $this->resources;
            if(!$resources){
             throw new InternalException(
                 "GroupNode should have a group resources node as child!",
             context:['groupNode'=>$this]
         );
            }
            return $resources;
         }

        private function getParent():XMLNodeBase{
            return $this->indexes ? $this->getGroupMembers() : $this->parent;
        }

        public function getParentObjectId(): object
        {
            return $this->getParent();
        }

        protected function moveUp(XMLContextBase $context): XMLNodeBase
        {
            $taskRes = $context->getTaskRes();
            /**
             * @var ?int $newGroupIndex
             */
            $newGroupIndex = array_pop($this->indexes);
            $taskRes->setCurrentGroupIndex($newGroupIndex);
            return $newGroupIndex !== null ? $this->getGroupMembers() : $this->parent;
        }

        protected function getParentName(): ?string
        {
            return $this->getParent()->name;
        }


        /**
         * @throws XMLInvalidElementException
         */
        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes, $context, $name);
           $config = TaskSrcConfig::get();
            $taskRes = $context->getTaskRes();
            if(count($this->indexes) >= $config->maxGroupDepth){
                $this->invalidElement(
                    getPosCallback:$context,
                description:"Max group depth '{$config->maxGroupDepth}' exceeded"
            );
            }

            $prevIndex = $taskRes->addGroup();
            if ($prevIndex !== null && Utils::lastArrayValue($this->indexes) !== $prevIndex) {
                $this->indexes[] = $prevIndex;
            }


        }

        /**
         * @throws XMLMissingRequiredElementsException
         */
        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $taskRes = $context->getTaskRes();
            if($taskRes->getNumOfResourcesInCurrentGroup() < 1){
                $this->missingRequiredElements(
                    [$this->getGroupResources()->name],
                    $context
                );
            }
            $group = $taskRes->getCurrentGroup();
            $exerciseCount = $taskRes->getExerciseCount();
            if($group->start >= $exerciseCount){
                $this->missingRequiredElements(
                    missingElements:[$this->getGroupMembers()->getName()],
                    getPosCallback:$context
                );
            }
            $group->length = $exerciseCount - $group->start;
        }
}
}
