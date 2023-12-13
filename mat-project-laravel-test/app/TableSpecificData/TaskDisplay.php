<?php

namespace App\TableSpecificData;

use App\Types\BackedEnumTrait;

enum TaskDisplay:string{
    use BackedEnumTrait;
    case HORIZONTAL = 'horizontal';
    case VERTICAL = 'vertical';
}