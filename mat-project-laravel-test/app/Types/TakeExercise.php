<?php

namespace App\Types;

use App\Helpers\CTakeExercise;
use App\Helpers\ExerciseType;

class TakeExercise
{
    public int $id;
    public string $instructions;
    public ExerciseType $type;
    public CTakeExercise $impl;

    public function __construct(int $id,string $instructions,ExerciseType $type,CTakeExercise $impl){
        $this->id=$id;
        $this->instructions=$instructions;
        $this->type=$type;
        $this->impl=$impl;
    }

}
