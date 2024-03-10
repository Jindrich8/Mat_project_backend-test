<?php

namespace App\Types {

    use Illuminate\Support\Str;
    use Illuminate\Support\Stringable;

    /**
     * @extends EasyMessageLogger<LoggerInterface,string>
     */
    class DebugLoggerInstance extends EasyMessageLogger
    {
        private static ?self $staticInstance = null;


        public static function instance():self{
            return (self::$staticInstance ??= new self());
        }


        /**
         * @var ?MessageLoggerInterface<string>
         */
        private ?MessageLoggerInterface $logger;
        private bool $dump;

        private function __construct(){
            $this->logger = new LogLogger();
            $this->dump = true;
        }

        

        public function logToChannelWContext($level, string|Stringable $message, mixed $value = null, mixed $channel = null): void
        {
            if (PHP_SAPI === 'cli') {
                if ($this->dump) {
                    $print = "\n";
                    if ($channel) {
                        $print .= Str::upper($channel) . ": ";
                    }
                    echo $print . $message;
                    dump($value);
                }
            } else {
                $this->logger->channel($channel)->log($level, $message, $value);
            }
        }

        public function performance(string $message, mixed $value = null)
        {
            $this->logToChannel(LOG_INFO, $message, $value, 'performance');
        }

        /**
         * @param MessageLoggerInterface<string> $logger
         */
        public function withLogger(MessageLoggerInterface $logger, callable $action, ?bool $dump = null)
        {
            $prevLogger = $this->logger;
            $this->logger = $logger;

            $prevDump = $this->dump;
            $this->dump = $dump ?? $this->dump;

            $action();

            $this->dump = $prevDump;
            $this->logger = $prevLogger;
        }

        protected function getChannel(mixed $channel): mixed
        {
            return $this->logger->channel($channel);
        }
    }
}