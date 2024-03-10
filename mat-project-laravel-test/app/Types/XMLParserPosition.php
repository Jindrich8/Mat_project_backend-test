<?php

namespace App\Types {

    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use XMLParser;

    /**
     * XML parser position
     *
     * Column - from 1
     * Line - from 1
     * ByteIndex - sometimes is not updated properly, but when it changes it should be correct
     *
     * Getting position:
     * Element start - element end ('>')
     * Element end - element end end ('>')
     * Element value - element value start, (last element value) = next construct start
     */
    class XMLParserPosition
    {
        public function __construct(
            private int $line = 1,
            private int $column = 1,
            private int $byteIndex = 0,
            private bool $byteIndexIsValid = true
        ) {
        }

        public function getLine(): int
        {
            return $this->line;
        }
        public function getColumn(): int
        {
            return $this->column;
        }
        public function getByteIndex(): ?int
        {
            return $this->byteIndexIsValid ? $this->byteIndex : null;
        }
        public function getRawByteIndex(): int
        {
            return $this->byteIndex;
        }
        public function getByteIndexIsValid(): bool
        {
            return $this->byteIndexIsValid;
        }

        public function setByteIndex(int $byteIndex,bool $canBeSame = false):bool{
            $isValid =  $this->byteIndex < $byteIndex || $canBeSame && $this->byteIndex <= $byteIndex;

            $this->byteIndex = $byteIndex;
            $this->byteIndexIsValid = $isValid;
            return $isValid;
        }

        public function advancePositionBy(int $columnOffset,int $lineOffset,int $byteOffset){
            $newColumn = $columnOffset;
            if($lineOffset === 0){
                $newColumn+=$this->column;
            }
            $newLine = $this->line+$lineOffset;
            $newByteIndex = $this->byteIndex+$byteOffset;
            $this->advancePositionTo($newColumn,$newLine,$newByteIndex);
        }

        private function byteIndexShouldBeValid(array $context){
            throw new InternalException(
                message:"Byte index should be valid.",
            context:[
                'this' => $this,
                ...$context
            ]);
        }

        public function advancePositionTo(int $newColumn,int $newLine,int $newByteIndex){
            if(!$this->byteIndexIsValid){
                $this->byteIndexShouldBeValid(
                    [
                'newColumn' => $newColumn,
                'newLine' => $newLine,
                'newByteIndex'=>$newByteIndex
                    ]);
            }

            if($newColumn < $this->column || $newLine < $this->line || $newByteIndex < $this->byteIndex){
                throw new InternalException("New position should be greater than previous one.",
                context:[
                    'this' => $this,
                    'newColumn' => $newColumn,
                    'newLine'=>$newLine,
                    'newByteIndex'=>$newByteIndex
                ]);
            }
            $this->column = $newColumn;
            $this->line = $newLine;
            $this->byteIndex = $newByteIndex;
        }

        public function getPosition(int &$column,int &$line,int|null &$byteIndex): self
        {
            $column =$this->column;
            $line = $this->line;
            $byteIndex = $this->getByteIndex();
            return $this;
        }

        public function getRawPosition(int &$column,int &$line,int &$byteIndex,bool &$byteIndexIsValid): self
        {
            $column =$this->column;
            $line = $this->line;
            $byteIndex = $this->byteIndex;
            $byteIndexIsValid = $this->byteIndexIsValid;
            return $this;
        }

        public static function getParserPosition(XMLParser $parser,?int &$column,?int &$line,?int &$byteIndex): void
        {
               $column = self::getValidPos(xml_get_current_column_number($parser), $parser);
               $line = self::getValidPos(xml_get_current_line_number($parser), $parser);
               $byteIndex = self::getValidPos(xml_get_current_byte_index($parser), $parser);

        }

        /**
         * @param int|false $pos
         * @param XMLParser $parser
         * @return int
         * @throws InvalidArgumentException
         */
        private static function getValidPos(int|false $pos, XMLParser $parser): int
        {
            if ($pos === false) {
                throw new InvalidArgumentException(
                    argumentName: "parser",
                    argumentValue: $parser,
                    isNotValidBecause: "method for getting position mark it as invalid, i.e. it failed to get position."
                );
            }
            return $pos;
        }

        public function reset(){
            $this->line = 1;
            $this->column = 1;
            $this->byteIndex = 0;
            $this->byteIndexIsValid = true;
        }

        public function updateFromParser(XMLParser $parser): self
        {
            self::getParserPosition(
                $parser,
            column:$column,
            line:$line,
            byteIndex:$byteIndex
        );
            $this->line = $line;
            $this->column = $column;
            $this->setByteIndex($byteIndex);
            return $this;
        }

        public function updateFromPos(self $pos):self{
            $this->column = $pos->column;
            $this->line = $pos->line;
            $this->byteIndex = $pos->byteIndex;
            $this->byteIndexIsValid = $pos->byteIndexIsValid;
            return $this;
        }

        public function fromParser(XMLParser $parser): self
        {
            $pos = new self();
            $pos->updateFromParser($parser);
            return $pos;
        }

        public function __toString()
        {
            return "column: " . $this->column .
            ", line: " . $this->line .
            ", byteIndex: " . $this->byteIndex  .
            ", byteIndexIsValid: " . $this->byteIndexIsValid;
        }
    }
}
