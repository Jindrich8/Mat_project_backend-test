<?php

namespace App\Types {

    use App\Exceptions\EnumConversionException;
    use BackedEnum;

    /**
     * @template T
     */
    trait BackedEnumTrait{
        
        /**
         * @return T[]
         */
        public static function getValues():array{
            return array_map(fn(BackedEnum $val)=>$val->value,static::cases());
        }

        /**
         * @param T $value
         * @return static
         * @throws EnumConversionException
         */
        public static function fromThrow(mixed $value):static{
            $enum = static::tryFrom($value);
            if($enum !== false) return $enum->value;
            throw new EnumConversionException(static::class,$value);
        }
    }
}