<?php

namespace App\Utils {

    class DebugUtils
    {

        public static function printStackTrace(){
            $e = new \Exception();
           echo "\nTrace:\n",$e->getTraceAsString(),"\n";
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
            if(!$areEqual) return $areEqual;

            $unpackedA = unpack('C*',$a);
            $unpackedB = unpack('C*',$b);

            $areEqual = $unpackedA === $unpackedB;
            if(!$areEqual) return $areEqual;
            $packedA = pack('C*',$a);
            $packedB = pack('C*',$b);
            $areEqual =self::stringsAreEqual($packedA,$packedB) 
            && self::stringsAreEqual($a,$packedA) 
            && self::stringsAreEqual($b,$packedB);
            
        }

        private static function stringsAreEqual(string $a, string $b):bool{
            return $a === $b && strlen($a) == strlen($b);
        }
    }
}