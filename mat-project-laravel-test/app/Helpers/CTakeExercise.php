<?php

namespace App\Helpers;


use App\Exceptions\InternalException;
use Illuminate\Contracts\Support\Arrayable;

interface CTakeExercise extends Arrayable
{
    /**
     * @throws InternalException
     * @param $value
     */
    public function setSavedValue($value):void;
}
