<?php

namespace App\Types {

    use App\Exceptions\InvalidArgumentException;
    use App\Utils\ValidateUtils;

    class ValidableInt
    {
        public readonly string $name;
        public readonly int $minInclusive;
        public readonly int $maxInclusive;

        public function __construct(string $name,int $maxInclusive, int $minInclusive){
            $this->name = $name;
            $this->minInclusive = $minInclusive;
            $this->maxInclusive = $maxInclusive;

            if($this->maxInclusive < $this->minInclusive){
                throw new InvalidArgumentException(
                    "maxInclusive",
                $maxInclusive,
                "Max number ('$maxInclusive') must be greater than or equal to min number ('$minInclusive')"
                );
            }
        }

        public function validate(string &$value,?int &$parsed):?string{
            $error = null;
            if(($parsed = ValidateUtils::validateInt($value,$this->minInclusive,$this->maxInclusive)) === null){
                $error = "Attribute '{$this->name}' value should be integer from '{$this->minInclusive}' to '{$this->maxInclusive}";
            }
            return $error;
        }
    }
}