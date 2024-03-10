<?php

namespace App\Helpers\CreateTask {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLValueParsingNode;
    use App\Types\XMLNodeBase;
    use App\Types\XMLContextBase;

    class ExerciseContentNode extends XMLValueParsingNode
    {

        public static function create(ExerciseNode $parent): self
        {
            return new self($parent);
        }


        private function __construct(ExerciseNode $exercise){
            parent::__construct(
                name: TaskSrcConfig::get()->exerciseContentName,
                parent: $exercise,
                maxCount: 1
            );
        }

        public function getContentNode(XMLContextBase $context): XMLNodeBase
        {
            return $context->getTaskRes()
                ->getExerciseContentNode(
                    parent: $this->parent,
                    name: $this->getName()
                );
        }
}
}
