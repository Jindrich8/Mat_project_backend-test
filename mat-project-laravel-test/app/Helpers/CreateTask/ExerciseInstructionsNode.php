<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XML\XMLContextBase;
    use App\Types\XML\XMLNodeBaseWParentNode;

    class ExerciseInstructionsNode extends XMLNodeBaseWParentNode
    {

        public static function create(ExerciseNode $parent){
            $node = new self($parent);
            return $node;
        }

        public function __construct(ExerciseNode $exercise){
            parent::__construct(
                parent:$exercise,
                maxCount:1,
            name:TaskSrcConfig::get()->exerciseInstructions->name
            );
        }



    public function appendValue(string $value, XMLContextBase $context): void
    {
        $taskRes = $context->getTaskRes();
        $lastExercise = $taskRes->getLastExercise();
        $lastExercise->instructions =($lastExercise->instructions ?? "").$value;
    }

        /**
         * @param XMLContextBase $context
         * @throws XMLInvalidElementValueException
         * @throws XMLMissingRequiredElementsException
         */
        protected function validate(XMLContextBase $context): void
    {
        parent::validate($context);
        $taskRes = $context->getTaskRes();
        $lastExercise = $taskRes->getLastExercise();

        $element = TaskSrcConfig::get()->exerciseInstructions;
        $instructions =$lastExercise->instructions  ?? '';
        $error = $element->validateWLength($instructions,$length);
        $lastExercise->instructions = $instructions;
        if($error){
           $this->invalidValue(
        description:$error
    );
        }
    }
}
}
