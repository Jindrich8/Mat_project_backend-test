<?php

namespace App\Types {

    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Stringable;
    use Psr\Log\LoggerInterface;
    use Throwable;

    /**
     * @template TChannel
     * @template TChannelName
     * @implements MessageLoggerInterface<TChannel>
     * @implements ChannelLoggerInterface<TChannel>
     */
    abstract class EasyMessageLogger implements MessageLoggerInterface, ChannelLoggerInterface
    {
        /**
         * @param TChannelName $channel
         */
        public function channel(mixed $channel): ValueLoggerInterface
        {
            return new EasyLogger($this, $this->getChannel($channel));
        }

        /**
         * @param TChannelName $channel
         * @return TChannel
         */
        protected abstract function getChannel(mixed $channel):mixed;

        public function warning(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_WARNING,$message,$value);
        }

        public function info(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_INFO,$message,$value);
        }

        public function debug(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_DEBUG,$message,$value);
        }

        public function emergency(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_EMERG,$message,$value);
        }

        public function alert(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_ALERT,$message,$value);
        }

        public function critical(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_CRIT,$message,$value);
        }

        public function error(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_ERR,$message,$value);
        }
        
        public function notice(string|Stringable $message, mixed $value = null): void
        {
            $this->log(LOG_NOTICE,$message,$value);
        }

        public function log($level, string|Stringable $message, mixed $value = null): void
        {
            $this->logToChannel($level,$message,$value);
        }

        public function logToChannel($level, string|Stringable $message, mixed $value = null, mixed $channel = null): void
        {
            if($value && is_callable($value)){
                try{
                $value = $value();
                }
                catch(Throwable $e){
                    $value = ['function' => $value,'exception' => $e];
                }
            }
            if(!is_array($value)){
                $value = [
                    ($value instanceof Throwable ?
                     'exception'
                      : 'value'
                      ) => $value
                    ];
            }
            $this->logToChannelWContext($level,$message,$value,$channel);
        }

        /**
         * @param TChannel $channel
         */
        public abstract function logToChannelWContext($level, string|Stringable $message, array $context = [], mixed $channel = null):void;
    }
}