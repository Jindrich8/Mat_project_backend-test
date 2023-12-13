<?php

namespace App\Types {

    use BackedEnum;

    trait BackedEnumTrait{
        public static function getValues():array{
            return array_map(fn(BackedEnum $val)=>$val->value,static::cases());
        }
    }
}