<?php

namespace App\TableSpecificData;

use App\Types\DBTranslationEnumTrait;
use App\Types\TranslableBackedEnum;

/**
 * @implements TranslableBackedEnum<TaskDifficulty>
 */
enum TaskDifficulty: int implements TranslableBackedEnum
{
    /**
     * @use DBTranslationEnumTrait<int>
     */
    use DBTranslationEnumTrait;
    case EASY = 0;
    case MEDIUM = 1;
    case HARD = 2;

    public function translateCase():string
    {
        return match($this){
            self::EASY => 'Lehká',
            self::MEDIUM => 'Střední',
            self::HARD => 'Těžká',
            default => $this->name
        };
    }
}
