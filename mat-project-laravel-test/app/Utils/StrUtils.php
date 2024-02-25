<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Http\Middleware\TrimStrings;
    use App\Types\CharIteratorKeyType;
    use App\Types\TrimType;
    use Doctrine\DBAL\Platforms\TrimMode;
    use Generator;
    use Illuminate\Support\Str;
    use IntlBreakIterator;
    use IntlChar;

    class StrUtils
    {

        private const TRIM_WHITES_NO_LINE_BREAK = " \r\t\v\0";
        private const TRIM_WHITES = " \n\r\t\v\0";

        /**
         * @return array{0:int,1:int}|false
         * Returns start index and end index of new line character or false if not found
         */
        public static function getNewLinePos(string $str,int $byteOffset = 0):array|false{
            $pos = strpos($str,"\n",$byteOffset);
            if($pos === false){
                return false;
            }
            if ($pos > $byteOffset && $str[$pos - 1] === "\r") {
                return [$pos-1,$pos];
            }
            return [$pos,$pos];
        }

        private static function getTrimWsSet(bool $includeLineBreak = true)
        {
            return $includeLineBreak ? self::TRIM_WHITES : self::TRIM_WHITES_NO_LINE_BREAK;
        }

        /**
         * @return string[]
         */
        public static function getChars(string $str):array{
            return mb_str_split($str,1,'UTF-8');
        }

        public static function skipAsciiWs(string $value, int $offset, ?int $len = null, bool $includeLineBreak = true): int
        {
            $newValue = substr($value, $offset, $len);
            $newValueLen = strlen($newValue);
            $trimmed = ltrim($newValue, self::getTrimWsSet($includeLineBreak));
            return $newValueLen - strlen($trimmed);
        }

        public static function generateEnumStr(string $prefix, int $minIncl, int $maxIncl, string $sep = ','): string
        {
            return $prefix . implode($sep . $prefix, range($minIncl, $maxIncl + 1));
        }

        /**
         * @return string
         * Returns first set key or empty string if not found
         */
        public static function utf8GetFirstSetKeyAndAdvancePos(
            string $str,
            array &$set,
            int &$columnPos,
            int &$linePos,
            int &$byteOffset
        ) {
            $foundKey = "";
            $byteOffsetForIter = $byteOffset;
            $chStart = $byteOffsetForIter;
            foreach (self::iterateOverString($str, byteOffset: $byteOffsetForIter) as $chEnd => $ch) {
                if (Utils::arrayHasKey($set, $ch)) {
                    $foundKey = $ch;
                    break;
                }
                if (StrUtils::utf8IsNewLineChar($ch)) {
                    ++$linePos;
                    $columnPos = 1;
                } else {
                    ++$columnPos;
                }
                $byteOffset = $chEnd;
                $chStart = $chEnd;
            }
            return $foundKey;
        }

        public static function length(string $str): int
        {
            return Str::length($str, 'UTF-8');
        }

        public static function utf8IsNewLineChar(string $char): bool
        {
            return $char === "\n" || $char === "\r\n";
        }

        public static function utf8GetCharAtIndex(string $str, int $index, int $byteOffset = 0): string
        {
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $start = 0;
            if ($byteOffset >= 1) {
                $start = $iter->following($byteOffset - 1);
            }
            if ($index > 0) {
                $start = $iter->next($index);
            }
            $end = $iter->next();
            if ($start < 0 || $end < 0) {
                return "";
            }
            return substr($str, $start, $end - $start);
        }

        public static function utf8ByteOffset(string $str, int $chOffset): int
        {
            $bOffset = self::utf8TryToGetNthCharByteOffset($str, $chOffset);
            if ($bOffset < 0) {
                throw new InternalException(
                    "Character offset '$chOffset' is out of range",
                    context: [
                        'chOffset' => $chOffset,
                        'str' => $str
                    ]
                );
            }
            return $bOffset;
        }

        public static function utf8TryToGetNthCharByteOffset(string $str, int $chOffset, int $byteOffset = 0): int
        {
            if ($chOffset < 0) {
                return -1;
            }
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $iter->following($byteOffset - 1);
            $bOffset = $iter->next($chOffset);
            if ($bOffset >= 0 && $bOffset > strlen($str)) {
                $bOffset = -1;
            }
            return $bOffset;
        }

        public static function utf8LtrimWhites(string $str, int &$trimmedCount,int $maxTrimCount = -1, bool $includeLineBreak = true): string
        {
          
            $prevLen = strlen($str);
            $res = ltrim($str, self::getTrimWsSet($includeLineBreak));
            $trimmedCount = $prevLen - strlen($res);
            if($maxTrimCount > 0 && $trimmedCount > $maxTrimCount){
                $res = substr($str,$maxTrimCount);
            }
            return $res;
        }


        public static function substrAsciiBetween(string $str, int $byteOffset, int $endByteIndex): string
        {
            return substr($str, $byteOffset, $endByteIndex - $byteOffset);
        }

        /**
         * @return Generator<int, string, mixed, void>
         * Return [chEnd => $ch] key value pairs
         */
        public static function iterateOverString(string $str, int $byteOffset = 0, int $len = -1, ?string $locale = null)
        {
            $strLen = strlen($str);
            if ($byteOffset < 0 || $byteOffset > $strLen) {
                throw new InternalException(
                    "Byte offset must be in 0..$strLen",
                    context: [
                        'str' => $str,
                        'byteOffset' => $byteOffset,
                        'len' => $len,
                        'locale' => $locale
                    ]
                );
            }
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $i = 0;
            $start = $byteOffset === 0 ? 0 :
                $iter->following($byteOffset - 1);
            while ($start >= 0) {
                $end = $iter->next();
                $char = substr($str, $start, $end - $start);
                yield $end => $char;
                $start = $end;
                ++$i;
            }
        }

        public static function trimWhites(string $str, TrimType $mode, bool $includeLineBreak = true)
        {
            switch ($mode) {
                case TrimType::TRIM_START:
                    return ltrim($str, self::getTrimWsSet($includeLineBreak));
                case TrimType::TRIM_END:
                    return rtrim($str, self::getTrimWsSet($includeLineBreak));
                case TrimType::TRIM_BOTH:
                    return trim($str, self::getTrimWsSet($includeLineBreak));
                default:
                    throw new InvalidArgumentException(
                        "mode",
                        $mode->name,
                        "this mode is not supported",
                        context: [
                            'supportedModes' =>
                            [
                                TrimType::TRIM_START->name, TrimType::TRIM_END->name, TrimType::TRIM_BOTH->name
                            ],
                            'mode' => $mode->name
                        ]
                    );
            }
        }

        public static function isTrimmableWhite(mixed $char, bool $includeLineBreak = true): bool
        {
            return match ($char) {
                " ", "\r", "\t", "\v", "\0" => true,
                "\n" => $includeLineBreak,
                default => false
            };
        }

        public static function isUtf8SafeAsciiChar(string $str, int $strByteLen = -1): bool
        {
            if ($strByteLen < 0) $strByteLen = strlen($str);
            return $strByteLen === 1 && ord($str) < 128;
        }

        public static function skipAsciiChar(string $str, string $skipChar, int $byteOffset): int
        {
            $byteLen = strlen($str);
            if ($byteOffset < 0 || $byteOffset > $byteLen) {
                throw new InternalException(
                    "ByteOffset should be in range 0..=str length ($byteLen)",
                    context: ['str' => $str, 'skipChar' => $skipChar, 'byteOffset' => $byteOffset]
                );
            }
            if (!self::isUtf8SafeAsciiChar($skipChar)) {
                throw new InternalException(
                    "Skipchar should be ascii character.",
                    context: ['str' => $str, 'skipChar' => $skipChar, 'byteOffset' => $byteOffset]
                );
            }
            $oldByteOffset = $byteOffset;
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            for (; $byteOffset < $byteLen && $str[$byteOffset] === $skipChar; ++$byteOffset);
            return $byteOffset - $oldByteOffset;
        }


        /**
         * @param string $str
         * @param int &$bytePos
         * @param int &$linePos
         * @param int &$columnPos
         * @param int $byteOffset
         * @return int
         * Returns count of skipped chars
         */
        public static function skipWhitesAndAdvancePos(string $str, int &$bytePos, int &$linePos, int &$columnPos, int $byteOffset = 0, int $maxCount = -1, bool $includingNewLines = true): int
        {
            $skippedCount = 0;
            $start = $byteOffset;
            if ($maxCount !== 0) {
                if ($maxCount < 0) {
                    $maxCount = strlen($maxCount);
                }
                foreach (self::iterateOverString($str, byteOffset: $byteOffset) as $end => $char) {
                    $newLine = $linePos;
                    $newCol = $columnPos + 1;
                    if (self::utf8IsNewLineChar($char)) {
                        if (!$includingNewLines) {
                            break;
                        }
                        $newLine += 1;
                        $newCol = 1;
                    }
                    if (!self::isTrimmableWhite($char)) {
                        break;
                    }
                    $linePos = $newLine;
                    $columnPos = $newCol;
                    // end - start = character length
                    $bytePos += $end - $start;
                    $skippedCount += 1;
                    $start = $end;
                    if ($skippedCount >= $maxCount) {
                        break;
                    }
                }
            }
            return $skippedCount;
        }
    }
}
