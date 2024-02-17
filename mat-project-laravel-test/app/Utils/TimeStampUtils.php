<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use Carbon\Carbon;
    use DateTime;
    use DateTimeZone;

    class TimeStampUtils
    {
        public static function tryParseIsoTimestamp(string $timestamp):Carbon|false|null{
           return Carbon::createFromFormat(
                format: DateTime::ATOM,
                time: $timestamp,
                timezone: new DateTimeZone('UTC')
           );
        }

        public static function tryParseIsoTimestampToUtc(string $timestamp):Carbon|false|null{
           $carbon = self::tryParseIsoTimestamp($timestamp);
           if($carbon){
            self::timestampToUtc($carbon);
           }
            return $carbon;
        }

        public static function timestampToUtc(Carbon &$timestamp):void{
            if(!$timestamp->isUtc()){
               $timestamp->setTimezone(DateTimeZone::UTC);
            }
        }

        public static function timestampToString(Carbon $timestamp):string{
            $str = $timestamp->format(DateTime::ATOM);
            if($str === false){
                throw new InternalException("Could not convert timestamp to string!",
                context:[
                    'timestamp' => $timestamp
                ]);
            }
            return $str;
        }
    }
}