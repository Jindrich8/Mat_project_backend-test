<?php

namespace App\Utils {

    use App\Types\DebugLoggerInstance;
    use App\Types\MessageLoggerInterface;

    class DebugLogger
    {
        private static function logger():DebugLoggerInstance{
            return DebugLoggerInstance::instance();
        }

        public static function withLogger(MessageLoggerInterface $logger, callable $action, ?bool $dump = null)
        {
            self::logger()->withLogger($logger,$action,$dump);
        }

        public static function log(string $message,mixed $value = null){
            self::logger()->info($message,$value);
        }

        public static function debug(string $message,mixed $value = null){
            self::logger()->debug($message,$value);
        }

        public static function performance(float $duration,string $durationUnit,string $message,mixed $value = null){
            self::logger()->performance($duration.$durationUnit.' - '.$message,$value);
        }
    }
}
