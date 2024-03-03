<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Types\ValidableString;

    class Config
    {
        public readonly ValidableString $correctText;
        public readonly ValidableString $wrongText;

        public function __construct(ValidableString $correctText, ValidableString $wrongText){
            $this->wrongText = $wrongText;
            $this->correctText = $correctText;
        }
    }
}