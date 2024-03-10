<?php

namespace App\Types {

    use Illuminate\Support\Stringable;

    /**
     * @template TChannel
     */
    class EasyLogger implements ValueLoggerInterface
    {
        /**
         * @var ChannelLoggerInterface<TChannel>
         */
        private ChannelLoggerInterface $logger;
        /**
         * @var TChannel $channel
         */
        private mixed $channel;
        
        /**
         * @param ChannelLoggerInterface<TChannel> $messageLoggerInterface
         * @param TChannel $channel
         */
        public function __construct(ChannelLoggerInterface $messageLoggerInterface,mixed $channel = null){
            $this->logger = $messageLoggerInterface;
            $this->channel = $channel;
        }

        public function warning(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_WARNING,$message,$value,$this->channel);
        }

        public function info(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_INFO,$message,$value,$this->channel);
        }

        public function debug(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_DEBUG,$message,$value,$this->channel);
        }

        public function emergency(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_EMERG,$message,$value,$this->channel);
        }

        public function alert(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_ALERT,$message,$value,$this->channel);
        }

        public function critical(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_CRIT,$message,$value,$this->channel);
        }

        public function error(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_ERR,$message,$value,$this->channel);
        }

        public function notice(string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel(LOG_NOTICE,$message,$value,$this->channel);
        }

        public function log($level, string|Stringable $message, mixed $value = null): void
        {
            $this->logger->logToChannel($level,$message,$value,$this->channel);
        }
    }
}