<?php

namespace App\Types {

    trait CreatableNodeTrait
    {
        /**
         * @template T impl XMLNodeBase
         * @param T $parent
         * @return static
         */
        public static function create($parent):static{
            return new static($parent);
        }
    }
}