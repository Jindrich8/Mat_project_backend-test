<?php

namespace App\Helpers\Exercises\FixErrors {

    class Config
    {
        public readonly string $correctTextName;
        public readonly string $wrongTextName;

        public function __construct(string $correctTextName, string $wrongTextName){
            $this->correctTextName = $correctTextName;
            $this->wrongTextName = $wrongTextName;
        }
    }
}