<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\InvalidBoundsError;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Dtos\Defs\Types\Request\TimestampRange;
    use App\Utils\TimeStampUtils;
    use Carbon\Carbon;

    class DtoHelper
    {
        /**
         * @param int &$min
         * @param int &$max
         * @param class-string<\IntBackedEnum> $enum
         * @return RangeError|null
         */
        public static function validateEnumRange(int &$min, int &$max,string $enum):RangeError|null{
            $rangeError = null;
            /**
             * @var \IntBackedEnum $minCase
             */
            $minCase = $enum::tryFrom($min);
             /**
             * @var \IntBackedEnum $maxCase
             */
            $maxCase = $enum::tryFrom($max);
            if ($minCase !== null && $maxCase !== null) {
                if ($minCase->value > $maxCase->value) {
                    $rangeError = RangeError::create()
                        ->setError(RangeError::MIN_MAX_SWAPPED);
                }
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
         * @return RangeError|array{Carbon,Carbon}
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
            return $rangeError;
        }
    }
}
