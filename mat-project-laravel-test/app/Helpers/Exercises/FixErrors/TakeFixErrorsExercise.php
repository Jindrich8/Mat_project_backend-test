<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Task\Take\Response\DefsExercise;
use App\Helpers\CTakeExercise;
use App\Dtos\Task\Take\Response;

class TakeFixErrorsExercise implements CTakeExercise
{
    private Response\FixErrorsTakeResponse $response;

    public function __construct(Response\FixErrorsTakeResponse $response)
    {
        $this->response = $response;
    }

    public function setAsContentTo(DefsExercise $exercise): void
    {
        $exercise->setDetails($this->response);
    }
}
