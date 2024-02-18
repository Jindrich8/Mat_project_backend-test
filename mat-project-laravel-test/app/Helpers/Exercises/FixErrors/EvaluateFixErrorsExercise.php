<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\Defs\Types\Review\ExerciseReview;
    use App\Dtos\Task\Review\Get\DefsExercise;
    use App\Helpers\CEvaluateExercise;

    class EvaluateFixErrorsExercise implements CEvaluateExercise
    {
        public function __construct( $res)
        {
            
        }

        public function evaluateAndSetAsContentTo(mixed $value, ExerciseReview $exercise): void
        {
            
        }
    }
}