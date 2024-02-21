<?php

namespace App\Exceptions {

    class EnumConversionException extends ConversionException
    {
        /**
         * @param class-string<\BackedEnum> $enum
         * @param mixed $value
         */
        public function __construct(string $enum,mixed $value)
        {
            parent::__construct($enum,$value);
        }
    }
}
