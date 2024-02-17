<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponse;
use App\Dtos\TaskInfo\Take\DefsExercise;
use App\Helpers\CTakeExercise;

class TakeFixErrorsExercise implements CTakeExercise
{
    private FixErrorsTakeResponse $response;

    public function __construct(FixErrorsTakeResponse $response)
    {
        $this->response = $response;
    }

    public function setAsContentTo(DefsExercise $exercise): void
    {
        $exercise->setDetails($this->response);
    }
}
