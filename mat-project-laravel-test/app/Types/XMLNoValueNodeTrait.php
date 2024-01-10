<?php

namespace App\Types {

    use App\Utils\StrUtils;

    trait XMLNoValueNodeTrait
    {
        public function appendValue(string $value, XMLContextBase $context): void
        {
             if(StrUtils::trimWhites($value,TrimType::TRIM_BOTH)){
                $this->valueNotSupported();
             }
        }
    }
}