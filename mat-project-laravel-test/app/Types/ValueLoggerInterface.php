<?php

namespace App\Types {

    use Illuminate\Support\Stringable;

    interface ValueLoggerInterface
    {
        public function log($level,string|Stringable $message,mixed $value = null):void;
        public function notice(string|Stringable $message,mixed $value = null):void;
        public function emergency(string|Stringable $message,mixed $value = null):void;
        public function warning(string|Stringable $message,mixed $value = null):void;
        public function error(string|Stringable $message,mixed $value = null):void;
        public function info(string|Stringable $message,mixed $value = null):void;
        public function debug(string|Stringable $message,mixed $value = null):void;
        public function critical(string|Stringable $message,mixed $value = null):void;
    }
}