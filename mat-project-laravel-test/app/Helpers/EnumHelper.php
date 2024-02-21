<?php

namespace App\Helpers {

    use App\Exceptions\EnumConversionException;
    use BackedEnum;

    class EnumHelper
    {
        /**
         * @template TEnum of \BackedEnum
         * @param class-string<TEnum> $enum
         * @param value-of<TEnum> $value
         * @return string
         */
        public static function translateFrom(string $enum, $value):string{
            $case =  self::fromThrow($enum,$value);
            return self::translate($case);
          }

          /**
         * @template TEnum of \BackedEnum
         * @param TEnum $case
         * @return string
         */
          public static function translate(BackedEnum $case){
            return $case->name;
        }

        /**
         * @template TEnum of \BackedEnum
         * @param class-string<TEnum> $enum
         * @param value-of<TEnum> $value
         * @return TEnum
         * @throws EnumConversionException
         */
        public static function fromThrow(string $enum,mixed $value):BackedEnum{
            /**
             * @var \BackedEnum $enum
             */
            $enum = $enum::tryFrom($value);
            if($enum !== false) return $enum;
            throw new EnumConversionException(static::class,$value);
        }

        /**
         * @template TEnum of \BackedEnum
         * @template T of value-of<TEnum>
         * @param class-string<TEnum> $enum
         * @return T[]
         */
        public static function getValues(string $enum):array{
            return array_map(fn(BackedEnum $val)=>$val->value,$enum::cases());
        }
    }
}