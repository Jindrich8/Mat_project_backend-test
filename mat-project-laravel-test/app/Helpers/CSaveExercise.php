<?php

namespace App\Helpers;

use App\Exceptions\BadSaveExerciseException;
use App\Types\Transformable;

interface CSaveExercise
{
    /**
     * @throws BadSaveExerciseException
     * @return Transformable
     */
    public function validate():Transformable;
}
