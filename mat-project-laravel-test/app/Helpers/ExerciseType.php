<?php

namespace App\Helpers;

use App\Types\BackedEnumTrait;
use App\Types\DBTranslationEnumTrait;

enum ExerciseType:string
{
    use BackedEnumTrait;
    use DBTranslationEnumTrait;
    case FillInBlanks = "FillInBlanks";
    case FixErrors = "FixErrors";
}
