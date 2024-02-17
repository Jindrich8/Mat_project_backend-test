<?php

namespace App\Helpers;

use App\Dtos\Task\Take\DefsExercise as TakeDefsExercise;
use App\Dtos\Task\Take\Response\DefsExercise;
use App\Exceptions\InternalException;

interface CTakeExercise
{

    public function setAsContentTo(TakeDefsExercise $exercise):void;
}
