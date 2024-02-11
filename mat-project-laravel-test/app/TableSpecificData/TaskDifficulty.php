<?php

namespace App\TableSpecificData;

use App\Types\BackedEnumTrait;

enum TaskDifficulty:int{
    use BackedEnumTrait;
    case EASY = 0;
    case MEDIUM = 1;
    case HARD = 2;
}