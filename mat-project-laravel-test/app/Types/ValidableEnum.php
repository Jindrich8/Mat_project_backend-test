<?php

namespace App\Types {

    use BackedEnum;

    /**
     * @template T of BackedEnum
     */
    class ValidableEnum
    {
        public readonly string $name;
        /**
         * @var array<string,T> $translate
         */
        public readonly array $translate;

        /**
         * ValidableEnumFlagsValue
         * @var int $flags
         */
        public readonly int $flags;

        public readonly BackedEnum $enum;

        public function hasFlag(int $flag):bool{
            return ValidableEnumFlags::hasFlag($this->flags,$flag);
        }

        /**
         * @param string $name
         * @param array<string,T> $translate
         * @param T $enum
         * @param int $flags
         * ValidableEnumFlagsValue
         */
        public function __construct(string $name,array $translate,BackedEnum $enum,int $flags = 0){
            $this->name = $name;
            $this->translate = $translate;
            $this->enum = $enum;

            $this->flags = $flags;
        }



        public function getAllowedEnumValues()
        {
            $allowed = array_keys($this->translate);
            if ($this->hasFlag(ValidableEnumFlags::ALLOW_ENUM_VALUES)) {
                array_push(
                    $allowed,
                    ...array_map(
                        fn (BackedEnum $case) => $case->value,
                        $this->enum::cases()
                    )
                );
            }
            return $allowed;
        }

        /**
         * @return string[]
         */
        public function getAllowedEnumStringValues():array
        {
           return array_map(fn($val)=>strval($val),$this->getAllowedEnumValues());
        }

        /**
         * @return T|null
         */
        public function validate(string $value):BackedEnum|null{
           $translatedValue = $this->translate[$value] ?? null;
           if($translatedValue === null && $this->hasFlag(ValidableEnumFlags::ALLOW_ENUM_VALUES)){
           $translatedValue = $this->enum::tryFrom($value);
           }
           return $translatedValue;
        }
    }
}
