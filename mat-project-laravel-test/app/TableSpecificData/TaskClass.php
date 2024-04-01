<?php

namespace App\TableSpecificData {

    use App\Types\DBTranslationEnumTrait;
    use App\Types\TranslableBackedEnum;

    /**
 * @implements TranslableBackedEnum<TaskClass>
 */
    enum TaskClass:int implements TranslableBackedEnum
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

        public function translateCase(): string
        {
            return match ($this) {
                self::ZS_1 => 'ZŠ 1',
                self::ZS_2 => 'ZŠ 2',
                self::ZS_3 => 'ZŠ 3',
                self::ZS_4 => 'ZŠ 4',
                self::ZS_5 => 'ZŠ 5',
                self::ZS_6 => 'ZŠ 6',
                self::ZS_7 => 'ZŠ 7',
                self::ZS_8 => 'ZŠ 8',
                self::ZS_9 => 'ZŠ 9',

                self::SS_1 => 'SŠ 1',
                self::SS_2 => 'SŠ 2',
                self::SS_3 => 'SŠ 3',
                self::SS_4 => 'SŠ 4',

                self::AFTER_SS => 'Po SŠ',
                default => $this->name
            };
        }
    }
}