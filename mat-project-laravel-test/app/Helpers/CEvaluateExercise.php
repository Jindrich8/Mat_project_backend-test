<?php

namespace App\Helpers;

use App\Dtos\Task\Review\Get\DefsExercise;
use App\Exceptions\BadSaveExerciseException;
use App\Types\Transformable;
use App\Dtos\Task\Review\Response;

interface CEvaluateExercise
{
    /**
     * This function evaluates fetched exercise with given value and sets the points and review to given exercise dto
     * @param mixed $value
     * @param DefsExercise $exercise
     * @return void
     */
   public function evaluateAndSetAsContentTo(mixed $value,DefsExercise $exercise):void;
}
