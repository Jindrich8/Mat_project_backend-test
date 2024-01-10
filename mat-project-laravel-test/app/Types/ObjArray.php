<?php

namespace App\Types {

    /**
     * @template TValue
     * @template TKey
     */
    class ObjArray
    {
        /**
         * @var array<TKey,TValue> $arr
         */
        public array $arr;

        /**
         * @param array<TKey,TValue> $arr
         */
        public function __construct(array $arr){
            $this->arr = $arr;
        }
    }
}