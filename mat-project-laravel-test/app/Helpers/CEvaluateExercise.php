<?php

namespace App\Helpers;

use App\Dtos\Defs\Types\Review\ExerciseReview;
use App\Exceptions\InvalidEvaluateValueException;
use Swaggest\JsonSchema\Structure\ClassStructure;

interface CEvaluateExercise
{
    /**
     * This function evaluates fetched exercise with given value and sets the points and review to given exercise dto
     * @param mixed $value
     * @param ExerciseReview $exercise
     * @return void
     * @throws InvalidEvaluateValueException thrown when given value is not a valid value for this exercise
     */
   public function evaluateAndSetAsContentTo(ClassStructure $value,ExerciseReview $exercise):void;
}
