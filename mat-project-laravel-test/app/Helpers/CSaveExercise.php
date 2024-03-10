<?php

namespace App\Helpers;

use App\Exceptions\BadSaveExerciseException;
use App\Types\TransformableInterface;

interface CSaveExercise
{
    /**
     * @throws BadSaveExerciseException
     * @return TransformableInterface
     */
    public function validate():TransformableInterface;
}
