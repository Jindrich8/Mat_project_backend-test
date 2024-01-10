<?php
namespace App\Utils;

use BackedEnum;
use Closure;
use ReflectionFunction;

class Utils{


    public static function arrayHasKey(array &$arr,string|int $key){
        return array_key_exists($key,$arr);
    }
    /**
     * @template T
     * @param T[] $array
     * @return T|null
     */
    public static function lastArrayValue(array &$array):mixed{
        return $array ? $array[array_key_last($array)] : null;
    }

    public static function wrapAndImplode(string $wrapStr,string $separator,array &$array):string{
        if(!$array) return "";
        return $wrapStr.implode($wrapStr.$separator,$array).$wrapStr;
    }

    public static function arrayToStr(array &$array):string{
        return self::wrapAndImplode("'",", ",$array);
    }

    /**
     * @param string|Closure $funcName
     */
    public static function getArgumentNamesViaReflection(string|Closure $funcName) {
        return array_map( fn( $parameter ) => $parameter->name,
            (new ReflectionFunction($funcName))->getParameters() );
    }

    

    public static function ifTrueAppendElseSet(string &$prop,string $value){
        if($prop){
            $prop.=$value;
        }
        else{
            $prop = $value;
        }
    }
}