<?php

namespace App\TableSpecificData {

    use App\Types\DBTranslationEnumTrait;

    enum TaskClass:int
    {
        /**
         * @use DBTranslationEnumTrait<int>
         */
        use DBTranslationEnumTrait;
        
        case ZS_1 = 0;
        case ZS_2 = 1;
        case ZS_3 = 2;
        case ZS_4 = 3;
        case ZS_5 = 4;
        case ZS_6 = 5;
        case ZS_7 = 6;
        case ZS_8 = 7;
        case ZS_9 = 8;

        case SS_1 = 9;
        case SS_2 = 10;
        case SS_3 = 11;
        case SS_4 = 12;
        
        case AFTER_SS = 13;
    }
}