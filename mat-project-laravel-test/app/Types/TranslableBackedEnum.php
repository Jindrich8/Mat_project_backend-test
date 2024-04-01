<?php

namespace App\Types {

    use BackedEnum;

    /**
     * @template T of BackedEnum
     */
    interface TranslableBackedEnum
    {
        public function translateCase():string;
    }
}