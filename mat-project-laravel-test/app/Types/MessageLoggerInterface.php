<?php

namespace App\Types {

    /**
     * @template TChannel
     */
    interface MessageLoggerInterface extends ValueLoggerInterface
    {
        /**
         * @param TChannel $channel
         */
        public function channel(mixed $channel):ValueLoggerInterface;
    }
}