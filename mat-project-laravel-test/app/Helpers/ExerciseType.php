<?php

namespace App\Helpers;

use App\Types\BackedEnumTrait;

enum ExerciseType:string
{
    use BackedEnumTrait;
    case FillInBlanks = "FillInBlanks";
    case FixErrors = "FixErrors";
}
