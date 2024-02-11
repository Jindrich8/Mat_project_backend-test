<?php

namespace App\Helpers;

use App\Dtos\TaskInfo\Take\DefsExercise as TakeDefsExercise;
use App\Dtos\TaskInfo\Take\Response\DefsExercise;
use App\Exceptions\InternalException;

interface CTakeExercise
{

    public function setAsContentTo(TakeDefsExercise $exercise):void;
}
