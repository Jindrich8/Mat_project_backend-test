<?php

namespace App\Helpers\CreateTask {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\CreatableNodeTrait;
    use App\Types\XMLValueParsingNode;
    use App\Types\XMLDynamicNodeBase;
    use App\Types\XMLNodeBase;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeValueType;

    class ExerciseContentNode extends XMLValueParsingNode
    {

        public static function create(ExerciseNode $parent){
            $node = new self($parent);
            return $node;
        }
        

        private function __construct(ExerciseNode $exercise){
            parent::__construct(
                parent:$exercise,
                maxCount:1,
            name:TaskSrcConfig::get()->exerciseContentName
            );
        }

        public function getContentNode(XMLContextBase $context): XMLNodeBase
        {
            return $context->getTaskRes()->getExerciseContentNode(name:$this->getName(),parent:$this->parent);
        }
}
}