<?php

namespace App\Types {

    use App\Exceptions\EnumConversionException;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\EnumHelper;
    use BackedEnum;

    /**
     * @template T
     * @phpstan-extends BackedEnum
     */
    trait BackedEnumTrait{

        /**
         * @return T[]
         */
        public static function getValues():array{
            /**
             * @var T[] $vals
             */
            $vals = EnumHelper::getValues(static::class);
            return $vals;
        }

        /**
         * @param value-of<static> $value
         * @return static
         * @throws EnumConversionException
         */
        public static function fromThrow(mixed $value):\BackedEnum{
            /**
             * @var static $value
             */
            $case = EnumHelper::fromThrow(static::class,$value);
            return $case;
        }
    }
}
