<?php

namespace App\Types {

    use App\Exceptions\InternalException;
    use App\Helpers\CreateTask\TaskRes;

    class XMLContextWOffset extends XMLContextBase
    {
        private XMLContextBase $context;
        private int $columnOffset;
        private int $lineOffset;
        private int $byteOffset;


        public function __construct(XMLContextBase $context, int $columnOffset, int $lineOffset, int $byteOffset)
        {
            if ($columnOffset < 0 || $lineOffset < 0 || $byteOffset < 0) {
                throw new InternalException(
                    "Column offset, line offset and byte offset should not be negative!",
                    context: [
                        'context' => $context,
                        'columnOffset' => $columnOffset,
                        'lineOffset' => $lineOffset,
                        'byteOffset' => $byteOffset
                    ]
                );
            }
            $this->context = $context;
            $this->columnOffset = $columnOffset;
            $this->lineOffset = $lineOffset;
            $this->byteOffset = $byteOffset;
        }

        public function getTaskRes(): TaskRes
        {
            return $this->context->getTaskRes();
        }

        public function getPos(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            $this->context->getPos($column, $line, $byteIndex);
            $column += $this->columnOffset;
            $line += $this->lineOffset;
            $byteIndex += $this->byteOffset;
        }
    }
}