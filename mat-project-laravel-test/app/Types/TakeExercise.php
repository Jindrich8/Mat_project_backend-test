<?php

namespace App\Types;

use App\Helpers\CTakeExercise;
use App\Helpers\ExerciseType;

class TakeExercise
{
    public int $id;
    public string $instructions;
    public CTakeExercise $impl;

    public function __construct(int $id,string $instructions,CTakeExercise $impl){
        $this->id=$id;
        $this->instructions=$instructions;
        $this->impl=$impl;
    }

}
