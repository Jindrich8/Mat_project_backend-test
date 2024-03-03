<?php

namespace App\Helpers\BareModels {

    class BareGroup
    {
        public int $start;
        public ?int $length;

        public function __construct(int $start){
            $this->start = $start;
            $this->length = null;
        }
    }
}