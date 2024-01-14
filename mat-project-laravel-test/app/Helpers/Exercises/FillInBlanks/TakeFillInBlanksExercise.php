<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\InternalTypes\FillInBlanksSavedValue\FillInBlanksSavedValue;
use App\Dtos\Task\Take\Response\DefsExercise;
use App\Helpers\CTakeExercise;
use App\Dtos\Task\Take\Response;

class TakeFillInBlanksExercise implements CTakeExercise
{
    private Response\FillInBlanksTakeResponse $response;

    public function __construct(Response\FillInBlanksTakeResponse $response)
    {
        $this->response = $response;
    }

    public function setAsContentTo(DefsExercise $exercise):void{
        $exercise->setDetails(Response\FillInBlanksTakeResponse::create()
        ->setContent($this->response));
    }

    
}
