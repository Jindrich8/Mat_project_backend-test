<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use Illuminate\Support\Str as SupportStr;

    class ValidateUtils
    {
        public static function validateInt(string $value,?int $inclusiveMin = null,?int $inclusiveMax = null):?int{
            $options = [
                'options'=>[],
                'flags'=>FILTER_NULL_ON_FAILURE
            ];
            $optionsOptions = &$options['options'];
            if($inclusiveMin !== null) $optionsOptions['min_range'] =$inclusiveMin;
            if($inclusiveMax !== null) $optionsOptions['max_range'] =$inclusiveMax;

            return filter_var($value,FILTER_VALIDATE_INT,$options);
        }

        public static function validateString(string &$value,?int &$length,int $minInclusiveLength = 0,int $maxInclusiveLength = PHP_INT_MAX):bool{
            $value =trim($value);
            if($length < 0){
                throw new InternalException(
                    message:"String length should not be negative",
                context:[
                    "value" => $value,
                "length" => $length,
                "min_length" => $minInclusiveLength,
                "max_length" => $maxInclusiveLength
                ]);
            }
            $length ??= SupportStr::length($value,encoding:'UTF-8');
            return $length >= $minInclusiveLength && $length <= $maxInclusiveLength;
        }
    }
}