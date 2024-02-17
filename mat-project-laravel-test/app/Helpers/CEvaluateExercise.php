<?php

namespace App\Helpers;

use App\Dtos\Defs\Types\Review\ExerciseReview;
use App\Exceptions\BadSaveExerciseException;
use App\Types\Transformable;
use App\Dtos\Task\Review\Response;

interface CEvaluateExercise
{
    /**
     * This function evaluates fetched exercise with given value and sets the points and review to given exercise dto
     * @param mixed $value
     * @param ExerciseReview $exercise
     * @return void
     */
   public function evaluateAndSetAsContentTo(mixed $value,ExerciseReview $exercise):void;
}
