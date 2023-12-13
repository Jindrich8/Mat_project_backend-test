<?php

namespace App\Types {

    class Coords
    {

        private static ?Coords $invalid = null;

        public static function getInvalid(){
            return (self::$invalid ??= new Coords(-1,-1));
        }

        public static function isValid(int $coord){
            return $coord >= 0;
        }

        public static function isInvalid(int $coord){
            return $coord < 0;
        }

        /**
         * @param int<-1,max> $x
         * @param int<-1,max> $y
         */
        public function __construct(
            public readonly int $x,
            public readonly int $y
        )
        {
            
        }

        /**
         * @param mixed $xKey
         * @param mixed $yKey
         * @param bool $includeInvalid
         * @return array<$xKey,$yKey>
         */
        public function toArray(mixed $xKey,mixed $yKey,bool $includeInvalid=false){
            $ret = [];
            if($includeInvalid){
                $ret = [
                    $xKey => $this->x,
                    $yKey => $this->y
                ];
            }
            else{
                if(self::isValid($this->x)){
                    $ret[$xKey] = $this->x;
                }
                if(self::isValid($this->y)){
                    $ret[$yKey] = $this->y;
                }
            }
            return $ret;
        }

        public function toArraySubsInvalidOnes(mixed $xKey,mixed $yKey,mixed $invalidValue){
            $x = $this->x;
            $y = $this->y;
            if(self::isInvalid($x)){
                $x = $invalidValue;
            }
            if(self::isInvalid($y)){
                $y = $invalidValue;
            }
            return [
                $xKey => $x,
                $yKey => $y
            ];
        }
    }
}