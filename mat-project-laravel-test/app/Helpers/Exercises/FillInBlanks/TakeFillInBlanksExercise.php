<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksTakeResponse;
use App\Dtos\InternalTypes\FillInBlanksSavedValue\FillInBlanksSavedValue;
use App\Dtos\TaskInfo\Take\DefsExercise;
use App\Helpers\CTakeExercise;
use App\Dtos\TaskInfo\Take\Response;

class TakeFillInBlanksExercise implements CTakeExercise
{
    private FillInBlanksTakeResponse $response;

    public function __construct(FillInBlanksTakeResponse $response)
    {
        $this->response = $response;
    }

    public function setAsContentTo(DefsExercise $exercise):void{
        $exercise->setDetails($this->response);
    }

    
}
