<?php

namespace App\Utils;

use Closure;
use ReflectionFunction;
use stdClass;

class Utils
{

    /**
     * @template T
     * @template TKey
     * @template R
     * @template RKey
     * @param callable(TKey $key,T $value):array{0:RKey,1:R} $map
     * @param array<TKey,T> $array
     * @return array<RKey,R>
     */
    public static function arrayMapWKey(callable $map, array &$array)
    {
        /**
         * @var array<RKey,R> $mapped
         */
        $mapped = [];
        foreach ($array as $key => $value) {
            /**
             * @var RKey $rKey
             * @var R $rValue
             */
            [$rKey, $rValue] = $map($value, $key);
            $mapped[$rKey] = $rValue;
        }
        return $mapped;
    }
    /**
     * @template T
     * @param T[] $arr
     * @return T|null
     */
    public static function tryGetFirstArrayValue(array $arr): mixed
    {
        return $arr ? $arr[Utils::arrayFirstKey($arr)] : null;
    }

    public static function isEmptyArray(mixed $value): bool
    {
        return self::isArray($value) && count($value) === 0;
    }

    /**
     * @template T
     * @return mixed|T
     */
    public static function tryToAccess(array|object $data, string $key, mixed $default = null): mixed
    {
        return is_object($data) ? $data->{$key} ?? $default : $data[$key] ?? $default;
    }

    public static function access(array|object $data, string $key): mixed
    {
        return is_object($data) ? $data->{$key} : $data[$key];
    }

    public static function set(array|object $data, string $key, mixed $value): void
    {
        if (is_object($data)) {
            $data->{$key} = $value;
        } else {
            $data[$key] = $value;
        }
    }

    /**
     * @param ?callable(mixed $value):mixed $parseValue
     */
    public static function recursiveAssocArrayToStdClass(array $arr, bool $canChange = false,?callable $parseValue = null)
    {
        $res = array_is_list($arr) ? [] : new stdClass;

        foreach($arr as $key => $value){
            $value = $parseValue ? $parseValue($value) : $value;
            if(is_array($value)){
                $value = self::recursiveAssocArrayToStdClass($value,$canChange,$parseValue);
            }
            if(is_array($res)){
                $res[]=$value;
            }
            else{
                $res->{$key} = $value;
            }
        }
        return $res;
    }

    /**
     * @param mixed &$arr
     * @return bool
     * @phpstan-assert-if-true array $arr
     */
    public static function isList(mixed &$arr): bool
    {
        return Utils::isArray($arr) && Utils::arrayIsList($arr);
    }

    /**
     * @param mixed &$arr
     * @return bool
     * @phpstan-assert-if-true array &$arr
     */
    public static function isArray(mixed &$arr): bool
    {
        return is_array($arr);
    }

    /**
     * @template T
     * @param array<array-key,T> &$arr
     * @phpstan-assert-if-true T[] &$arr
     */
    public static function arrayIsList(array &$arr): bool
    {
        return array_is_list($arr);
    }
    /**
     * @template T
     * @param array<array-key,T> &$arr
     * @return T|null
     */
    public static function arrayShift(array &$arr): mixed
    {
        return array_shift($arr);
    }

    /**
     * @template T
     * @param array<T,mixed> $arr
     * @return T|null
     */
    public static function arrayLastKey(array $arr): string|int|null
    {
        return array_key_last($arr);
    }

    /**
     * @template T
     * @param array<T,mixed> &$arr
     * @return T|null
     */
    public static function arrayFirstKey(array &$arr): string|int|null
    {
        return array_key_first($arr);
    }

    public static function arrayHasKey(array &$arr, string|int $key)
    {
        return array_key_exists($key, $arr);
    }

    /**
     * @template T
     * @param T[] $array
     * @return T|null
     */
    public static function lastArrayValue(array &$array): mixed
    {
        return $array ? $array[array_key_last($array)] : null;
    }

    public static function wrapAndImplode(string $wrapStr, string $separator, array &$array): string
    {
        if (!$array) return "";
        return $wrapStr . implode($wrapStr . $separator . $wrapStr, $array) . $wrapStr;
    }

    public static function arrayToStr(array &$array): string
    {
        return self::wrapAndImplode("'", ", ", $array);
    }

    /**
     * @param string|Closure $funcName
     * @return array|string[]
     * @throws \ReflectionException
     */
    public static function getArgumentNamesViaReflection(string|Closure $funcName)
    {
        return array_map(
            fn ($parameter) => $parameter->name,
            (new ReflectionFunction($funcName))->getParameters()
        );
    }



    public static function ifTrueAppendElseSet(string &$prop, string $value)
    {
        if ($prop) {
            $prop .= $value;
        } else {
            $prop = $value;
        }
    }

    public static function getAccessor(array|object &$value): callable
    {
        return is_object($value) ?
            static fn (object $value, string $prop) => $value->{$prop}
            : static fn (array &$value, string $prop) => $value[$prop];
    }
}
