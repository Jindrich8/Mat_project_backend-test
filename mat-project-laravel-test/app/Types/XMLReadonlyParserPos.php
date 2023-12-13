<?php

namespace App\Types {

    use App\Exceptions\InvalidArgumentException;
    use XMLParser;

    class XMLReadonlyParserPos
    {
        public function __construct(
            public readonly int $line =0,
            public readonly int $column =0,
            public readonly int $byteIndex=0
            ){

        }

        public function byteIndexIsValid():bool{
            return XMLParserPosition::byteIndexPositionIsValid(
                column:$this->column,
                line:$this->line,
                byteIndex:$this->byteIndex
            );
        }

        public function fromParserPos(XMLParserPosition $pos){
            return new self(
                line:$pos->line,
                column:$pos->column,
                byteIndex:$pos->byteIndex
            );
        }

        public function fromParser(XMLParser $parser):self{
            [$line,$column,$byteIndex] = XMLParserPosition::getParserPosition($parser);
            $pos = new self(
                line:$line,
                column:$column,
                byteIndex:$byteIndex
            );
            return $pos;
        }
    }
}