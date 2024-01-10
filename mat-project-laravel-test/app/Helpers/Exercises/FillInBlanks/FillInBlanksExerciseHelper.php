<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;

class FillInBlanksExerciseHelper implements CExerciseHelper
{
    private ?CreateFillInBlanksExercise $createHelper;

    public function __construct(){
        $this->createHelper = null;
    }

    public function fetchTake(array $ids): array
    {
        return [];
    }

    public function fetchSave(array $ids): array
    {
        return [];
    }

    public function getCreateHelper(): CCreateExerciseHelper
    {
        return $this->createHelper ??= new CreateFillInBlanksExercise();
    }
}
