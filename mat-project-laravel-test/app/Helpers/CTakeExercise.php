<?php

namespace App\Helpers;

use App\Dtos\Defs\Endpoints\Task\Take\DefsExercise as TakeDefsExercise;

interface CTakeExercise
{

    public function setAsContentTo(TakeDefsExercise $exercise):void;
}
