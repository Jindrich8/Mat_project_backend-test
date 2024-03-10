<?php

namespace App\Types {

    use Illuminate\Support\Stringable;

    /**
     * @template TChannel
     */
    interface ChannelLoggerInterface
    {
        /**
         * @param TChannel $channel
         */
        public function logToChannel($level, string|Stringable $message, mixed $value = null,mixed $channel = null): void;
    }
}