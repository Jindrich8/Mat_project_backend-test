<?php

namespace App\Types {

    use Illuminate\Support\Facades\Log;
    use Stringable;

    abstract class EasyLogger implements MessageLogger
    {
        public function warning(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_WARNING,$message,$context);
        }

        public function info(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_INFO,$message,$context);
        }

        public function debug(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_DEBUG,$message,$context);
        }

        public function emergency(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_EMERG,$message,$context);
        }

        public function alert(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_ALERT,$message,$context);
        }

        public function critical(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_CRIT,$message,$context);
        }

        public function error(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_ERR,$message,$context);
        }

        public function notice(string|Stringable $message, array $context = []): void
        {
            $this->log(LOG_NOTICE,$message,$context);
        }
    }
}