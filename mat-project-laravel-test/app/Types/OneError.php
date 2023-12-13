<?php

namespace App\Types\ErrorRes {

    class OneError
    {
        public readonly string $message;
        public readonly int $code;
        public readonly int $status;
        public readonly string $help;
    }
}