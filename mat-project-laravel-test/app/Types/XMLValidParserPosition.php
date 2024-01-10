<?php

namespace App\Types {

    use App\Exceptions\InternalException;


    class XMLValidParserPosition implements GetXMLParserPosition
    {
        private int $line;
        private int $column;
        private int $byteIndex;

        public function __construct(
            int $line = 1,
            int $column = 1,
            int $byteIndex = 0,
        ) {
            $this->setPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
        }

        public function getPos(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            $column = $this->column;
            $line = $this->line;
            $byteIndex = $this->byteIndex;
        }

        public function setPos(int $column, int $line, int $byteIndex): void
        {
            self::validatePos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
            $this->column = $column;
            $this->line = $line;
            $this->byteIndex = $byteIndex;
        }

        public function setPosFromProvider(GetXMLParserPosition $posProvider): void
        {
            $posProvider->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            $this->setPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
        }

        public static function isPosValid(int $column, int $line, int $byteIndex): bool
        {
            return ($column === 1 && $line === 1 && $byteIndex === 0)
                || ($column >= 1 && $line >= 1 && $byteIndex > 0);
        }

        private static function validatePos(int $column, int $line, int $byteIndex): void
        {
            if (!self::isPosValid(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            )) {
                throw new InternalException(
                    message: "Position should be valid!",
                    context: ['column' => $column, 'line' => $line, 'byteIndex' => $byteIndex]
                );
            }
        }

        public function __toString()
        {
            return "column: " . $this->column .
                ", line: " . $this->line .
                ", byteIndex: " . $this->byteIndex;
        }
    }
}
