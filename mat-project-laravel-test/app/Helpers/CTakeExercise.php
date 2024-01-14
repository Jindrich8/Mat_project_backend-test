<?php

namespace App\Helpers;

use App\Dtos\Task\Take\Response\DefsExercise;
use App\Exceptions\InternalException;

interface CTakeExercise
{

    public function setAsContentTo(DefsExercise $exercise):void;
}
