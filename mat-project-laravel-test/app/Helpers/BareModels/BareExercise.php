<?php

namespace App\Helpers\BareModels {

    use App\Helpers\ExerciseType;

    class BareExercise
    {
        public ?string $instructions;
        public ?ExerciseType $exerciseType;
        public ?int $weight;
        public ?int $order;

        public function __construct(){
            $this->instructions = null;
            $this->exerciseType = null;
            $this->weight = null;
            $this->order = null;
        }
    }
}