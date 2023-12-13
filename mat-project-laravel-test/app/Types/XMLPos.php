<?php

namespace App\Types {

    class XMLPos
    {
        public readonly int $line;
        public readonly int $column;
        public readonly int $byteIndex;

        public function __construct(int $line, int $column, int $byteIndex){
            $this->line = $line;
            $this->column = $column;
            $this->byteIndex = $byteIndex;
        }
    }
}