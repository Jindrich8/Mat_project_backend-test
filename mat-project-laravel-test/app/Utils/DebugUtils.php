<?php

namespace App\Utils {

    use BackedEnum;
    use Exception;
    use UnitEnum;
    use App\Utils\DebugLogger;

    class DebugUtils
    {

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
           DebugLogger::debug("Trace",$e->getTraceAsString());
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
