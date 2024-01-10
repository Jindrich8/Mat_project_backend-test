<?php

namespace App\Types {

    use App\Exceptions\InternalException;

    class Substring
    {
        private int $byteOffset;
        private string $str;

        public function __construct(){
            $this->str = '';
            $this->byteOffset = 0;
        }

        public function setStr(string $str,int $byteOffset){
            if($byteOffset < 0){
                throw new InternalException("Byte offset must be positive",
            context:['str' => $str,'byteOffset'=> $byteOffset]
        );
            }
            if($byteOffset > strlen($str)){
                throw new InternalException("Byte offset must be smaller or equal to string length",
            context:['str' => $str,'byteOffset'=> $byteOffset]
        );
            }
            $this->byteOffset = $byteOffset;
            $this->str = $str;
        }

        public function substr():string{
            return substr($this->str,$this->byteOffset);
        }
    }
}