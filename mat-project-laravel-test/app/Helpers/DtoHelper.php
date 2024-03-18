<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\InvalidBoundsError;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Utils\TimeStampUtils;
    use Carbon\Carbon;

    class DtoHelper
    {
        /**
         * @template TEnum of \IntBackedEnum
         * @param int $min
         * @param int $max
         * @param class-string<TEnum> $enum
         * @return RangeError|array{0:TEnum,1:TEnum}
         */
        public static function validateEnumRange(int $min, int $max,string $enum):RangeError|array{
            $rangeError = null;
            /**
             * @var TEnum $minCase
             */
            $minCase = $enum::tryFrom($min);
             /**
             * @var TEnum $maxCase
             */
            $maxCase = $enum::tryFrom($max);
            if ($minCase !== null && $maxCase !== null) {
                if ($minCase->value <= $maxCase->value) {
                    return [$minCase, $maxCase];
                }
                $rangeError = RangeError::create()
                    ->setError(RangeError::MIN_MAX_SWAPPED);
            } else {
                $error = InvalidBoundsError::create();
                if($minCase === null){
                    $error->setInvalidMin();
                }
                if($maxCase === null){
                    $error->setInvalidMax();
                }
                $rangeError = RangeError::create()
                    ->setError($error);
            }
            return $rangeError;
        }

         /**
         * @param string $minTimestamp
         * @param string $maxTimestamp
         * @return RangeError|array{?Carbon,?Carbon}
         */
        public static function validateTimestampRange(?string $minTimestamp, ?string $maxTimestamp):RangeError|array{
            $rangeError = null;
            $min = null;
            if($minTimestamp !== null){
            $min = TimeStampUtils::tryParseIsoTimestampToUtc($minTimestamp);
            }
            $max = null;
            if($maxTimestamp !== null){
            $max = TimeStampUtils::tryParseIsoTimestampToUtc($maxTimestamp);
            }
            if ($min && $max) {
                if ($min->lte($max)) {
                    return [$min, $max];
                }
                $rangeError = RangeError::create()
                    ->setError(RangeError::MIN_MAX_SWAPPED);
            } else if($minTimestamp !== null && $maxTimestamp !== null){
                $error = InvalidBoundsError::create();
                if(!$min){
                    $error->setInvalidMin();
                }
                if(!$max){
                    $error->setInvalidMax();
                }
                $rangeError = RangeError::create()
                    ->setError($error);
            }
            else{
                return [$min, $max];
            }
            return $rangeError;
        }
    }
}
