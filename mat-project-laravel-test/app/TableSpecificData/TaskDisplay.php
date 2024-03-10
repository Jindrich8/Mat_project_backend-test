<?php

namespace App\TableSpecificData;

use App\Types\DBTranslationEnumTrait;

enum TaskDisplay: int
{
    /**
     * @use DBTranslationEnumTrait<int>
     */
    use DBTranslationEnumTrait;
    case HORIZONTAL = 0;
    case VERTICAL = 1;
}
