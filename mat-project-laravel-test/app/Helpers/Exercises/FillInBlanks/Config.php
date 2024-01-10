<?php

namespace App\Helpers\Exercises\FillInBlanks{

    class Config
    {
        public readonly string $uiCmpStart;
        public readonly string $uiCmpEnd;
        public readonly string $cmbValuesSep;
        public readonly string $escape;

        

        public function __construct(string $uiCmpStart, string $uiCmpEnd, string $cmbValuesSep, string $escape){
            $this->uiCmpStart = $uiCmpStart;
            $this->uiCmpEnd = $uiCmpEnd;
            $this->cmbValuesSep = $cmbValuesSep;
            $this->escape = $escape;
        }
    }
}