<?php

namespace App\Helpers\BareModels {

    use App\Helpers\ExerciseType;

    class BareExercise
    {
        public string $instructions;
        public ExerciseType $exerciseType;
        public int $weight;
    }
}