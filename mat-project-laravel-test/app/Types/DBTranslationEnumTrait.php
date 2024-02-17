<?php

namespace App\Types {

    /**
     * @template T
     */
    trait DBTranslationEnumTrait
    {
        /**
         * @use BackedEnumTrait<T>
         */
        use BackedEnumTrait;
        /**
         * @param T $value
         * @return string
         */
        public static function translateFrom(mixed $value):string{
          $case =  self::fromThrow($value);
          return self::translate($case);
        }

        public static function translate(self $case){
            return $case->name;
        }
    }
}