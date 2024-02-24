<?php

namespace App\Types {

    use App\Helpers\EnumHelper;

    /**
     * @template T
     * @phpstan-extends \BackedEnum
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
         return EnumHelper::translateFrom(static::class,$value);
        }

        public function translate(){
            return EnumHelper::translate($this);
        }
    }
}
