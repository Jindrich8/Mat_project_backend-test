<?php

namespace App\Types;

use App\Helpers\CEvaluateExercise;
use App\Helpers\ExerciseType;

class EvaluateExercise
{
    public readonly int $id;
    public readonly string $instructions;
    public readonly int $weight;
    public readonly ExerciseType $type;
    public readonly CEvaluateExercise $impl;

    public function __construct(int $id, int $weight, string $instructions, ExerciseType $type, CEvaluateExercise $impl)
    {
        $this->id = $id;
        $this->weight = $weight;
        $this->instructions = $instructions;
        $this->type = $type;
        $this->impl = $impl;
    }
}
