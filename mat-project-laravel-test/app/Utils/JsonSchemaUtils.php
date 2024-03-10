<?php

namespace App\Utils {

    class JsonSchemaUtils
    {
        /**
         * @return string[]
         */
        public static function getPathProps(string $path):array{
            $res = [];
            $startFragment = '->properties:';
            $startFragmentLen = strlen($startFragment);
            $pos = 0;
            while (true) {
                $pos = strpos($path, $startFragment,$pos);
                if ($pos === false) break;
                $pos+=$startFragmentLen;
                $endPos = strpos($path, '->', $pos);
                if ($endPos === false) {
                    $res[] = substr($path, $pos);
                    break;
                }
                $res[] = substr($path, $pos, $endPos-$pos);
                $pos = $endPos;
            }
            return $res;
        }

        public static function filterError(string $error):string{
            $message = $error;
            $regex = '/(.*?)\\s*,?(?:data:|at\\s|#|\\$|{|\\[)/u';
                if (preg_match(pattern: $regex, subject: $error, matches: $matches)) {
                    $message = $matches[1];
                }
                $message = rtrim($message, ', ');
                return $message;
        }

        /**
         * @param string[] $path
         */
        public static function formatError(string $error,array $path = []):string{
            $messagePosfix = $path ? (' at ' . implode('->', $path)) : '';
            $message = rtrim($error, ', ') . $messagePosfix . '.';
            return $message;
        }

        
    }
}