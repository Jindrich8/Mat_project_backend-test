<?php

namespace App\Utils {

    use Generator;

    class GeneratorUtils
    {
        /**
         * @template TKey
         * @template TYield
         * @template TSend
         * @param \Generator<TKey,TYield,TSend> $generator
         * @param TKey|null &$key
         * @param TYield|null &$value
         * @return ($key is null ? false : ($value is null ? false : true))
         */
        public static function getCurrent(Generator $generator,mixed &$key,mixed &$value):bool{
           return ($value = $generator->current()) !== null 
           && ($key = $generator->key());
        }
    }
}