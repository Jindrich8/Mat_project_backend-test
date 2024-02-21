<?php

namespace Dev\DtoGen {

    use Illuminate\Support\Str;

    class StrUtils
    {

        public static function trimStart(string $str,string $trim){
            if ($trim === "" || $str === "") {
                return $str;
            }

            $quotedSkip = preg_quote($trim, '/');
            $matchRes = preg_match("/^(?>$quotedSkip+)(.*)$/u", $str, $matches);
            switch ($matchRes) {
                case 1:
                    return  $matches[1];

                case false:
                    $skipLen = mb_strlen($trim);
                    while(mb_substr($str, 0, $skipLen) === $trim){
                        $str = mb_substr($str,$skipLen);
                    }
                    return $str;

                default:
                    return $str;
        }
    }

    public static function trimWhitesFromStart(string $str):string{
        if ($str === "") {
            return $str;
        }
        /** @noinspection PhpRegExpInvalidDelimiterInspection */
        /** @noinspection PhpRegExpRedundantModifierInspection */
        $regex = <<<'EOF'
        /^\s+/u
        EOF;
        $matchRes = preg_replace($regex, '',$str);
        if($matchRes === null) {
            return ltrim($str);
        }
        return $matchRes;
}

        public static function skip(string $str, string $skip, int $offset = 0, int $length = -1, string $encoding = null): int
        {
            if ($skip === "") {
                return 0;
            }
            if ($length < 0) {
                $length = mb_strlen($str, $encoding);
            }
            $quotedSkip = preg_quote($skip, '/');
            $matchRes = preg_match("/^.{$offset}$quotedSkip+()/u", $str, $matches, PREG_OFFSET_CAPTURE);
            switch ($matchRes) {
                case 1:
                    return  $matches[1][1] - $offset;

                case false:
                    $skipLen = mb_strlen($skip, $encoding);
                    $newOffset = $offset;
                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                    for (; $newOffset < $length && mb_substr($str, $newOffset, $skipLen) === $skip; ++$newOffset);
                    return $newOffset - $offset;

                default:
                    return 0;
            }
        }



        public static function strStart(string $str, string $prefix): string
        {
            if (!Str::startsWith($str, $prefix)) {
                $str = $prefix . $str;
            }
            return $str;
        }

        /**
         * @param string $delimiter
         * @param string $string
         * @param string $limit
         * @param bool $ignoreEmptyParts
         * @return string[]|false
         * an exploded array or FALSE if an error occurred.
         */
        public static function explode(string $delimiter, string $string, int $limit = -1, bool $ignoreEmptyParts = true): array|false
        {
            $delimiter = preg_quote($delimiter, '/');
            $flags = 0;
            if ($ignoreEmptyParts) {
                $flags |= PREG_SPLIT_NO_EMPTY;
            }
            return preg_split("/$delimiter/u", $string, $limit, $flags);
        }

        /**
         * @param string[] $delimiters
         * @param string $string
         * @param string $limit
         * @param bool $ignoreEmptyParts
         * @return string[]|false
         * an exploded array or FALSE if an error occurred.
         */
        public static function explodeByArray(array $delimiters, string $string, int $limit = -1, bool $ignoreEmptyParts = true): array|false
        {
            $delimiters = array_map(fn ($del) => preg_quote($del, '/'), $delimiters);
            $regex = '(' . implode("|", $delimiters) . ')';
            $flags = 0;
            if ($ignoreEmptyParts) {
                $flags |= PREG_SPLIT_NO_EMPTY;
            }
            return preg_split("/$regex/u", $string, $limit, $flags);
        }

        /**
         * @param string $search
         * @param string $replace
         * @param string $subject
         * @return string|null
         * returns string with replaced parts or null if error occurred
         */
        public static function replace(string $search, string $replace, string $subject): string|null
        {
            $search = preg_quote($search, '/');
            return preg_replace("/$search/u", $replace, $subject);
        }
    }
}
