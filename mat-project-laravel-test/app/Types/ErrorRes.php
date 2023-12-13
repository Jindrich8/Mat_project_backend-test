<?php

namespace App\Types\ErrorRes {

    /**
     * @template T
     */
    class ErrorRes
    {
        /**
         * @var T $data
         */
        public readonly mixed $data;

        /**
         * @var T $data
         */
        public function __construct(mixed $data){
            $this->data = $data;
        }
    }
}