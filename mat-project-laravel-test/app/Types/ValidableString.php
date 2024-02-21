<?php

namespace App\Types {

    use App\Exceptions\InvalidArgumentException;
    use App\Utils\ValidateUtils;

    class ValidableString
    {
        public readonly string $name;
        public readonly int $minLen;
        public readonly int $maxLen;

        public function __construct(string $name,int $maxLen, int $minLen = 0){
            $this->name = $name;
            $this->minLen = $minLen;
            $this->maxLen = $maxLen;

            if($this->maxLen < $this->minLen){
                throw new InvalidArgumentException(
                    "maxLen",
                $maxLen,
                "Max length ('$maxLen') must be greater than or equal to min length ('$minLen')"
                );
            }
        }

        public function validate(string &$value):string|null{
          return $this->validateWLength($value,$length);
        }

        /**
         * @param string &$value
         * @param ?int &$length
         * * If length is **null**, then it is set to calculated length, if length was calculated,
         * * if it is **negative**, exception is thrown,
         * * otherwise it is used as value length.
         * @return string|null
         */
        public function validateWLength(string &$value,?int &$length):string|null{
            $error = null;
            if(!ValidateUtils::validateString($value,$length,$this->minLen,$this->maxLen)){
                $error = "Value of '{$this->name}' element should be string with length from '{$this->minLen}' to '{$this->maxLen}'.";
            }
            return $error;
        }
    }
}
