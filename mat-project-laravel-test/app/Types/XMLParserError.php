<?php

namespace App\Types {

    class XMLParserError
    {
        public readonly int $errorCode;
        public readonly string $errorMessage;
        public readonly int $column;
        public readonly int $line;
        public readonly int $byteIndex;

        public function __construct(int $errorCode, string $errorMessage,int$column,int $line,int $byteIndex){
            $this->errorCode = $errorCode;
            $this->errorMessage = $errorMessage;
            $this->column = $column;
            $this->line = $line;
            $this->byteIndex = $byteIndex;
        }
    }
}