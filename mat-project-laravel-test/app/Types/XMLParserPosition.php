<?php

namespace App\Types {

    use App\Exceptions\InvalidArgumentException;
    use XMLParser;

    class XMLParserPosition
    {
        public function __construct(
            public int $line =0,
            public int $column =0,
            public int $byteIndex=0
            ){

        }

        public function byteIndexIsValid():bool{
            return self::byteIndexPositionIsValid(
                column:$this->column,
                line:$this->line,
                byteIndex:$this->byteIndex
            );
        }

        public static function byteIndexPositionIsValid(int $column,int $line,int $byteIndex):bool{
            return $byteIndex !== 0 || $column === 0 && $line === 0;
        }
/**
 * @param XMLParser $parser
 * @return @param array{column:int,line:int,byteIndex:int}
 */
        public static function getParserPosition(XMLParser $parser):array{
            return [
                self::getValidPos(xml_get_current_column_number($parser),$parser),
                self::getValidPos(xml_get_current_line_number($parser),$parser),
                self::getValidPos(xml_get_current_byte_index($parser),$parser)
            ];
        }

        /**
         * @param int|false $pos
         * @param XMLParser $parser
         * @return int
         * @throws InvalidArgumentException
         */
        private static function getValidPos(int|false $pos,XMLParser $parser):int{
            if($pos === false){
                throw new InvalidArgumentException(
                    argumentName:"parser",
                argumentValue:$parser,
                isNotValidBecause:"method for getting position mark it as invalid, i.e. it failed to get position."
            );
        }
            return $pos;
        }

        public function updateFromParser(XMLParser $parser):void{
            [$this->line,$this->column,$this->byteIndex] = self::getParserPosition($parser);
        }

        public function fromParser(XMLParser $parser):self{
            $pos = new self();
            $pos->updateFromParser($parser);
            return $pos;
        }
    }
}