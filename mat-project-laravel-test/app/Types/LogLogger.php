<?php

namespace App\Types {

    use Illuminate\Support\Facades\Log;
    use Stringable;

    class LogLogger implements MessageLogger
    {
        public function warning(string|Stringable $message, array $context = []): void
        {
            Log::warning($message,$context);
        }

        public function info(string|Stringable $message, array $context = []): void
        {
            Log::info($message,$context);
        }

        public function debug(string|Stringable $message, array $context = []): void
        {
            Log::debug($message,$context);
        }

        public function emergency(string|Stringable $message, array $context = []): void
        {
            Log::emergency($message,$context);
        }

        public function alert(string|Stringable $message, array $context = []): void
        {
            Log::alert($message,$context);
        }

        public function critical(string|Stringable $message, array $context = []): void
        {
            Log::critical($message,$context);
        }

        public function error(string|Stringable $message, array $context = []): void
        {
            Log::error($message,$context);
        }

        public function notice(string|Stringable $message, array $context = []): void
        {
            Log::notice($message,$context);
        }

        public function log($level, string|Stringable $message, array $context = []): void
        {
            switch($level){
                case LOG_ERR:
                    Log::error($message,$context);
                    break;
                case LOG_DEBUG:
                    Log::debug($message,$context);
                    break;
                case LOG_INFO:
                    Log::info($message,$context);
                    break;
                case LOG_WARNING:
                    Log::warning($message,$context);
                    break;
                case LOG_EMERG:
                    Log::emergency($message,$context);
                    break;
                case LOG_ALERT:
                    Log::alert($message,$context);
                    break;
                case LOG_CRIT:
                    Log::critical($message,$context);
                    break;
                default:
                    Log::log($level,$message,$context);
                    break;
            }
        }
    }
}