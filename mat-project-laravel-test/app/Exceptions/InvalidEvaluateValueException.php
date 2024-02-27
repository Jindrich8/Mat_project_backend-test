<?php

namespace App\Exceptions {

    use Exception;

    /**
     * Thrown when an evaluateAndSetAsContentTo method is called with different exercise value as an argument.
     */
    class InvalidEvaluateValueException extends Exception
    {
        public function __construct(){
            parent::__construct();
        }
    }
}