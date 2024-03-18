<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use Carbon\Carbon;
    use DateTime;
    use DateTimeZone;
    use Throwable;

    class TimeStampUtils
    {
        public static function createFromTimestampUtc(string $timestamp){
           $carbon = new Carbon($timestamp);
           self::timestampToUtc($carbon);
           return $carbon;
        }

        public static function tryParseIsoTimestamp(string $timestamp):Carbon|null{
            DebugLogger::log("tryParseIsoTimestamp: '$timestamp'");
            $carbon = null;
            try{
           $carbon = new Carbon($timestamp,'UTC');// Carbon::createFromFormat(DateTime::ATOM,$timestamp,'UTC');
            }
            catch(Throwable $e){

            }
           return $carbon ?: null;
        }

        public static function tryParseIsoTimestampToUtc(string $timestamp):Carbon|null{
           $carbon = self::tryParseIsoTimestamp($timestamp);
           if($carbon){
            self::timestampToUtc($carbon);
           }
            return $carbon;
        }

        public static function parseIsoTimestampToUtc(string $timestamp):Carbon{
            $carbon = self::tryParseIsoTimestampToUtc($timestamp);
            if(!$carbon){
                throw new InternalException("Could not parse timestamp '$timestamp' to utc.",
                context:[
                    'timestamp' => $timestamp
                ]
                );
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

        public static function timestampNowUtcString():string{
            $timestamp = Carbon::now();
            self::timestampToUtc($timestamp);
            return self::timestampToString($timestamp);
        }
    }
}
