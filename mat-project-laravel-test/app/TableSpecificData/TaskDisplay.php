<?php

namespace App\TableSpecificData;

use App\Types\BackedEnumTrait;

enum TaskDisplay:int{
    use BackedEnumTrait;
    case HORIZONTAL = 0;
    case VERTICAL = 1;
}