<?php

namespace App\Utils {

    use App\Types\LogLogger;
    use App\Types\MessageLogger;
    use BackedEnum;
    use Exception;
    use Illuminate\Log\Logger;
    use Illuminate\Support\Facades\Log;
    use UnitEnum;

    class DebugUtils
    {
        private static ?MessageLogger $logger = null;
        private static bool $dump = true;

        private static function logger(){
        return (self::$logger ??= new LogLogger());
        }

        public static function withLogger(MessageLogger $logger,callable $action,?bool $dump = null){
            $prevLogger = self::$logger;
            self::$logger = $logger;
            $prevDump = self::$dump;
            self::$dump = $dump ?? self::$dump;
            $action();
            self::$dump = $prevDump;
            self::$logger = $prevLogger;
        }

        private static function logWithLevel(int $level,string $message,mixed $value = null){
             if(is_callable($value)){
                $value = $value();
            }
            if(PHP_SAPI === 'cli'){
                if(self::$dump){
                echo '\n'.$message;
                dump($value);
                }
            }
            else{
                self::logger()->log($level,$message,context:['value' => $value]);
            }
        }

        public static function log(string $message,mixed $value = null):void{
           self::logWithLevel(LOG_INFO,$message,$value);
        }

        public static function warning(string $message,mixed $value = null):void{
           self::logWithLevel(LOG_WARNING,$message,$value);
        }

        public static function debug(string $message,mixed $value = null){
            self::logWithLevel(LOG_DEBUG,$message,$value);
        }

        public static function enumToStr(UnitEnum|BackedEnum $enum)
        {
            return $enum instanceof BackedEnum ?
            self::backedEnumToStr($enum)
            : self::unitEnumToStr($enum);
        }

        public static function unitEnumToStr(UnitEnum $unitEnum){
            return $unitEnum::class."::$unitEnum->name";
        }

        public static function backedEnumToStr(BackedEnum $backedEnum){
            return $backedEnum::class."::{$backedEnum->name} => '$backedEnum->value'";
        }

        public static function printStackTrace(){
            $e = new Exception();
           DebugUtils::log("Trace",$e->getTraceAsString());
        }
        public static function jsonEncode(mixed $value):string{
            return json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }

        public static function isValidUtf8(string $text):bool{
           $decodedText = json_decode('"'.str_replace('"', '\\"', $text).'"');
           return self::stringsAreBinaryEqual($decodedText,$text);
        }

        public static function stringsAreBinaryEqual(string $a, string $b):bool{
            $areEqual = self::stringsAreEqual($a,$b);
            if(!$areEqual) return false;

            $unpackedA = unpack('C*',$a);
            $unpackedB = unpack('C*',$b);

            $areEqual = $unpackedA === $unpackedB;
            if(!$areEqual) return false;
            $packedA = pack('C*',$a);
            $packedB = pack('C*',$b);
            return self::stringsAreEqual($packedA,$packedB)
            && self::stringsAreEqual($a,$packedA)
            && self::stringsAreEqual($b,$packedB);
        }

        /**
         * @param string $a
         * @param string $b
         * @return bool
         */
        private static function stringsAreEqual(string $a, string $b):bool{
            return $a === $b && strlen($a) == strlen($b);
        }
    }
}
