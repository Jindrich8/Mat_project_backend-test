<?php
namespace App\Utils;

use BackedEnum;
use Closure;
use ReflectionFunction;
use stdClass;
use UnitEnum;

class Utils{
    /**
     * @template T
     * @return mixed|T
     */
     public static function tryToAccess(array|object $data,string $key,mixed $default = null):mixed{
        return is_object($data) ? $data->{$key} ?? $default : $data[$key] ?? $default;
    }

    public static function access(array|object $data,string $key):mixed{
        return is_object($data) ? $data->{$key} : $data[$key];
    }

    public static function set(array|object $data,string $key,mixed $value):void{
        if(is_object($data)){
            $data->{$key} = $value;
        }
        else{
            $data[$key] = $value;
        }
    }

    public static function recursiveAssocArrayToStdClass(array &$arr,bool $canChange=false){
        $rootObj = new stdClass;
        $stack = [];
        $obj = $rootObj;
        $myArr = &$arr;
        if(!$canChange){
            $temp = $arr;
            $myArr = &$temp;
        }
       while(true){
       $arrKey = Utils::arrayFirstKey($myArr);
       if($arrKey === null){
        $stackKey = Utils::arrayLastKey($stack);
        if($stackKey === null){
           break;
        }
        [$obj,&$myArr] = $stack[$stackKey];
        unset($stack[$stackKey]);
        continue;
       }
       $value = &$myArr[$arrKey];
       unset($myArr[$arrKey]);
       if(Utils::isArray($value) && !Utils::isList($value)){
        $newObj = new stdClass();
        $obj->{$arrKey} = $newObj;
         $stack[]=[$obj,&$myArr];
        
         $myArr = &$value;
         $obj = $newObj;
       }
       else{
        $obj->{$arrKey} = $value;
       }
       }
       return $obj;
    }
      /**
     * @param mixed &$arr
     * @phpstan-assert-if-true array $arr
     */
    public static function isList(mixed &$arr):bool{
        return Utils::isArray($arr) && Utils::arrayIsList($arr);
    }

     /**
     * @param mixed &$arr
     * @phpstan-assert-if-true array &$arr
     */
    public static function isArray(mixed &$arr):bool{
        return is_array($arr);
    }

    /**
     * @template T
     * @param array<array-key,T> &$arr
     * @phpstan-assert-if-true T[] &$arr
     */
    public static function arrayIsList(array &$arr):bool{
        return array_is_list($arr);
    }
    /**
     * @template T
     * @param array<array-key,T> &$arr
     * @return T|null
     */
    public static function arrayShift(array &$arr):mixed{
        return array_shift($arr);
    }

      /**
     * @template T
     * @param array<T,mixed> &$arr
     * @return T|null
     */
    public static function arrayLastKey(array &$arr):string|int|null{
        return array_key_last($arr);
    }
  
    /**
     * @template T
     * @param array<T,mixed> &$arr
     * @return T|null
     */
    public static function arrayFirstKey(array &$arr):string|int|null{
        return array_key_first($arr);
    }

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
        return $wrapStr.implode($wrapStr.$separator.$wrapStr,$array).$wrapStr;
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