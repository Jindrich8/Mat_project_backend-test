<?php

namespace App\Types {

    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Stringable as SupportStringable;
    use Psr\Log\LoggerInterface;

    /**
     * @extends EasyMessageLogger<LoggerInterface,string>
     */
    class LogLogger extends EasyMessageLogger
    {
        private LoggerInterface $defaultLogger;

        public function __construct(){
            $this->defaultLogger = Log::driver();
        }

        protected function getChannel(mixed $channel): mixed
        {
            return Log::channel($channel);
        }

        public function logToChannelWContext($level, string|SupportStringable $message, array $context = [], mixed $channel = null): void
        {
            $logger = $channel ?? $this->defaultLogger;
            switch($level){
                case LOG_ERR:
                    $logger->error($message,$context);
                    break;
                case LOG_DEBUG:
                    $logger->debug($message,$context);
                    break;
                case LOG_INFO:
                    $logger->info($message,$context);
                    break;
                case LOG_WARNING:
                    $logger->warning($message,$context);
                    break;
                case LOG_EMERG:
                    $logger->emergency($message,$context);
                    break;
                case LOG_ALERT:
                    $logger->alert($message,$context);
                    break;
                case LOG_CRIT:
                    $logger->critical($message,$context);
                    break;
                default:
                    $logger->log($level,$message,$context);
                    break;
            }
        }
    }
}