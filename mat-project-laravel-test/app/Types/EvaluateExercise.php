<?php

namespace App\Types;

use App\Helpers\CEvaluateExercise;

class EvaluateExercise
{
    public int $id;
    public string $instructions;
    public int $weight;
    public CEvaluateExercise $impl;

    public function __construct(int $id,int $weight,string $instructions,CEvaluateExercise $impl){
        $this->id=$id;
        $this->weight=$weight;
        $this->instructions=$instructions;
        $this->impl=$impl;
    }

}
