<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\Task\Review\Get\DefsExercise;
    use App\Helpers\CEvaluateExercise;

    class EvaluateFixErrorsExercise implements CEvaluateExercise
    {
        public function __construct( $res)
        {
            
        }

        public function evaluateAndSetAsContentTo(mixed $value, DefsExercise $exercise): void
        {
            
        }
    }
}