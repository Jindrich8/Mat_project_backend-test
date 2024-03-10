<?php

namespace App\TableSpecificData;

use App\Types\DBTranslationEnumTrait;

enum TaskDifficulty: int
{
    /**
     * @use DBTranslationEnumTrait<int>
     */
    use DBTranslationEnumTrait;
    case EASY = 0;
    case MEDIUM = 1;
    case HARD = 2;
}
