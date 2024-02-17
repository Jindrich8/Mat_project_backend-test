<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\InvalidBoundsError;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Dtos\Defs\Types\Request\TimestampRange;
    use App\Utils\TimeStampUtils;

    class DtoHelper
    {
          /**
         * @param int &$min
         * @param int &$max
         * @param class-string<IntBackedEnum> $enum
         */
        public static function validateEnumRange(int &$min, int &$max,string $enum):RangeError|null{
            $rangeError = null;
            /**
             * @var IntBackedEnum
             */
            $minCase = $enum::tryFrom($min);
             /**
             * @var IntBackedEnum
             */
            $maxCase = $enum::tryFrom($max);
            if ($minCase !== null && $maxCase !== null) {
                if ($minCase->value > $maxCase->value) {
                    $rangeError = RangeError::create()
                        ->setError(RangeError::MIN_MAX_SWAPPED);
                }
            } else {
                $rangeError = RangeError::create()
                    ->setError(
                        InvalidBoundsError::create()
                            ->setInvalidMin($minCase === null)
                            ->setInvalidMax($maxCase === null)
                    );
            }
            return $rangeError;
        }

         /**
         * @param string $minTimestamp
         * @param string $maxTimestamp
         * @return RangeError|array{\Carbon\Carbon,\Carbon\Carbon}
         */
        public static function validateTimestampRange(string $minTimestamp, string $maxTimestamp):RangeError|array{
            $rangeError = null;
            $min = TimeStampUtils::tryParseIsoTimestampToUtc($minTimestamp);
            $max = TimeStampUtils::tryParseIsoTimestampToUtc($maxTimestamp);
            if ($min && $max) {
                if ($min->lte($max)) {
                    return [$min, $max];
                }
                $rangeError = RangeError::create()
                    ->setError(RangeError::MIN_MAX_SWAPPED);
            } else {
                $rangeError = RangeError::create()
                    ->setError(
                        InvalidBoundsError::create()
                            ->setInvalidMin((bool)$min)
                            ->setInvalidMax((bool)$max)
                    );
            }
            return $rangeError;
        }
    }
}