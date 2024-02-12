<?php

namespace App\Exceptions {

    class ConversionException extends InternalException{

        public function __construct(string $type,mixed $value){
            parent::__construct("Could not convert value '$value' to type '$type'",
        context:[
            'type' =>$type,
            'value' =>$value
        ]);
        }

        public function getConversionType():string{
            return $this->context()['type'];
        }

        public function getConversionValue():mixed{
            return $this->context()['value'];
        }
    }
}