<?php

namespace App\Types {

    class ValidableEnumFlags
    {
        /**
         * @var int ALLOW_ENUM_VALUES
         */
        public const ALLOW_ENUM_VALUES = 1 << 0;

        public static function hasFlag(int $flags,int $flag):bool{
            return ($flags & $flag) === $flag;
        }
    }
}