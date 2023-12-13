<?php

namespace App\Helpers\CreateTask {

    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Helpers\CreateTask\XMLOneUseNode;
    use App\TableSpecificData\TaskDisplay;
    use Illuminate\Support\Str;

    class ExerciseNode extends XMLNodeBase
    {
        public function __construct(string $name,?XMLNodeBase $parent = null){
            parent::__construct($name,[],$parent);
        }

        function validateStart(iterable $attributes, TaskRes $taskRes, ?string $name = null): void
        {
            parent::validateStart($attributes,$taskRes,$name);
        }

        function getRequiredAttributes(): array
        {
            // TODO:
            throw null;
        }

        function getNonRequiredAttributes(): array
        {
            // TODO
            throw null;
        }
       function appendValue(string $value, TaskRes $taskRes, callable $getParserPosition): void
       {
        throw null;
       }

        function validate(TaskRes $taskRes): void
        {
            throw null;
        }
    }
}