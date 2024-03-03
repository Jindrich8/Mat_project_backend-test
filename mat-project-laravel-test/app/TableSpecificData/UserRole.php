<?php

namespace App\TableSpecificData {

    use App\Types\BackedEnumTrait;
    use App\Types\DBTranslationEnumTrait;

    enum UserRole:int
    {
        /**
         * @use BackedEnumTrait<int>
         */
        use BackedEnumTrait;
        /**
         * @use DBTranslationEnumTrait<int>
         */
        use DBTranslationEnumTrait;
        case NONE = 0;
        case TEACHER = 1;
    }
}