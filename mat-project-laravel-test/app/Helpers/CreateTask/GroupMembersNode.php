<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBaseWParentNode;

    class GroupMembersNode extends XMLNodeBaseWParentNode
    {

        private ?ExerciseNode $exerciseNode;

        private function getExerciseNode(): ExerciseNode
        {
            $exercise = $this->exerciseNode;
            if(!$exercise){
             throw new InternalException(
                 "GroupMembersNode should have a exercise node as child!",
             context:['groupMembersNode'=>$this]
         );
            }
            return $exercise;
         }

        public static function create(GroupNode $parent): GroupMembersNode
        {
            $node = new self($parent);
            $node->exerciseNode = ExerciseNode::create($node);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild($node->exerciseNode)
                ->addChildWithPossiblyDifferentParent($parent)
            );
            return $node;
        }

        public function __construct(GroupNode $group){
            $config = TaskSrcConfig::get();
            parent::__construct(
                name: $config->groupMembersName,
                parent: $group,
                isValueNode:false
            );
            $this->exerciseNode = null;
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes,$context,$name);
            $taskRes = $context->getTaskRes();
            $group = $taskRes->getCurrentGroup();
            $exerciseCount = $taskRes->getExerciseCount();
            if($group->start < $exerciseCount){
                $this->tooManyElements(
                    getPosCallback:$context,
                    maximum:1
                );
            }
        }

        /**
         * @throws XMLMissingRequiredElementsException
         */
        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $taskRes = $context->getTaskRes();
            $group = $taskRes->getCurrentGroup();
            $exerciseCount = $taskRes->getExerciseCount();
            if($group->start >= $exerciseCount){
                $this->missingRequiredElements(
                    missingElements: [$this->getExerciseNode()->name],
                    getPosCallback: $context
                );
            }
        }
}
}
