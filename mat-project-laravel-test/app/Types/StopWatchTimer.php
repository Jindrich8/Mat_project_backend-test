<?php

namespace App\Types {

    use App\Utils\DebugLogger;

    class StopWatchTimer
    {
        /**
         * @template TRet
         * @param callable():TRet $action
         * @param ?callable(TRet $res):mixed $transformResForLog
         * @return TRet
         */
        public static function run(string $name,callable $action,?callable $transformResForLog = null){
            DebugLogger::logger()->performance("START '$name'");
            $start = microtime(true);
            $res = $action();
            $end = microtime(true);
            $value = $transformResForLog ? $transformResForLog($res) : $res;
            DebugLogger::performance($end-$start,'s',$name,$value);
            return $res;
        }
    }
}