<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\CreatableNodeTrait;
    use App\Types\GetXMLParserPosition;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeValueType;

    class GroupMembersNode extends XMLNodeBaseWParentNode
    {
        use XMLNoValueNodeTrait;

        private ?ExerciseNode $exerciseNode;

        private function getExerciseNode(){
            $exercise = $this->exerciseNode;
            if(!$exercise){
             throw new InternalException(
                 "GroupMembersNode should have a exercise node as child!",
             context:['groupMembersNode'=>$this]
         );
            }
            return $exercise;
         }

        public static function create(GroupNode $parent){
            $node = new self($parent);
            $node->exerciseNode = ExerciseNode::create($node);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild($node->exerciseNode)
            );
            return $node;
        }

        public function __construct(GroupNode $group){
            $config = TaskSrcConfig::get();
            parent::__construct(
                parent:$group,
            name:$config->groupMembersName
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

        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $taskRes = $context->getTaskRes();
            $group = $taskRes->getCurrentGroup();
            $exerciseCount = $taskRes->getExerciseCount();
            if($group->start >= $exerciseCount){
                $this->missingRequiredElements(
                    getPosCallback:$context,
                    missingElements:[$this->getExerciseNode()->name]
                );
            }
        }
}
}