<?php

namespace App\Types {

    trait ConstructableTrait{
        public static function construct():static{
            return new static;
        }
    }
}