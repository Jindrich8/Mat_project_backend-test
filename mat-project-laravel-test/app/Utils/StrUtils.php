<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Http\Middleware\TrimStrings;
    use App\Types\CharIteratorKeyType;
    use App\Types\TrimType;
    use Doctrine\DBAL\Platforms\TrimMode;
    use Illuminate\Support\Str;
    use IntlBreakIterator;
    use IntlChar;

    class StrUtils
    {

        public static function generateEnumStr(string $prefix,int $minIncl,int $maxIncl,string $sep = ','):string{
            return $prefix.implode($sep.$prefix,range($minIncl,$maxIncl+1));
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
            ){
                $foundKey = "";
                $byteOffsetForIter = $byteOffset;
                $chStart = $byteOffsetForIter;
                foreach(self::iterateOverString($str,byteOffset:$byteOffsetForIter) as $chEnd => $ch){
                    if(Utils::arrayHasKey($set,$ch)){
                        $foundKey = $ch;
                        break;
                    }
                    if(StrUtils::utf8IsNewLineChar($ch)){
                        ++$linePos;
                        $columnPos=1;
                    }
                    else{
                        ++$columnPos;
                    }
                    $byteOffset=$chEnd;
                    $chStart=$chEnd;
                }
                return $foundKey;
        }

        public static function length(string $str):int{
            return Str::length($str,'UTF-8');
        }

        public static function utf8IsNewLineChar(string $char):bool{
            return $char === "\n" || $char === "\r\n";
        }

        public static function utf8GetCharAtIndex(string $str,int $index,int $byteOffset = 0):string{
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $start = 0;
            if($byteOffset >= 1){
            $start = $iter->following($byteOffset-1);
            }
            if($index > 0){
            $start = $iter->next($index);
            }
            $end = $iter->next();
            if($start < 0 || $end < 0){
                return "";
            }
           return substr($str,$start,$end-$start);
        }

        public static function utf8ByteOffset(string $str,int $chOffset):int{
           $bOffset =self::utf8TryToGetNthCharByteOffset($str,$chOffset);
           if($bOffset < 0){
            throw new InternalException("Character offset '$chOffset' is out of range",
            context:[
                'chOffset' =>$chOffset,
                'str' =>$str
                ]
        );
           }
           return $bOffset;
        }

        public static function utf8TryToGetNthCharByteOffset(string $str,int $chOffset,int $byteOffset = 0):int{
            if($chOffset < 0){
                return -1;
            }
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $iter->following($byteOffset-1);
            $bOffset = $iter->next($chOffset);
            if($bOffset >= 0 && $bOffset > strlen($str)){
                $bOffset = -1;
            }
            return $bOffset;
        }

        public static function utf8LtrimWhites(string $str, int &$trimmedCount): string
        {
            $prevLen = strlen($str);
            $str = ltrim($str);
            $trimmedCount = $prevLen - strlen($str);
            return $str;
        }


        public static function substrAsciiBetween(string $str,int $byteOffset,int $endByteIndex):string{
            return substr($str,$byteOffset,$endByteIndex-$byteOffset);
        }

        /**
         * @return \Generator<int, string, mixed, void>
         * Return [chEnd => $ch] key value pairs
         */
        public static function iterateOverString(string $str,int $byteOffset = 0, int $len = -1, ?string $locale = null)
        {
            $strLen = strlen($str);
            if($byteOffset < 0 || $byteOffset > $strLen){
                throw new InternalException("Byte offset must be in 0..$strLen",
                context:[
                    'str'=>$str,
                    'byteOffset'=>$byteOffset,
                    'len'=>$len,
                    'locale'=>$locale
                ]);
            }
            $iter = IntlBreakIterator::createCharacterInstance();
            $iter->setText($str);
            $i = 0;
            $start = $byteOffset === 0 ? 0 :
            $iter->following($byteOffset-1);
            while($start >= 0){
                $end = $iter->next();
                $char = substr($str,$start,$end-$start);
                yield $end => $char;
                $start = $end;
                ++$i;
            }
        }

        public static function trimWhites(string $str, TrimType $mode)
        {
            switch ($mode) {
                case TrimType::TRIM_START:
                    return ltrim($str);
                case TrimType::TRIM_END:
                    return rtrim($str);
                case TrimType::TRIM_BOTH:
                    return trim($str);
                    default:
                    throw new InvalidArgumentException("mode",$mode->name,
                    "this mode is not supported",
                    context:['supportedModes'=>
                    [
                        TrimType::TRIM_START->name,TrimType::TRIM_END->name,TrimType::TRIM_BOTH->name
                    ],
                    'mode' => $mode->name
                        ]
                );
            }
        }

        public static function isTrimmableWhite(mixed $char): bool
        {
            return match ($char) {
                " " => true,
                "\n" => true,
                "\r" => true,
                "\t" => true,
                "\v" => true,
                "\0" => true,
                default => false
            };
        }

        public static function isUtf8SafeAsciiChar(string $str,int $strByteLen = -1):bool{
            if($strByteLen < 0) $strByteLen = strlen($str);
            return $strByteLen === 1 && ord($str) < 128;
        }

        public static function skipAsciiChar(string $str,string $skipChar,int $byteOffset):int{
            $byteLen = strlen($str);
            if($byteOffset < 0 || $byteOffset > $byteLen){
                throw new InternalException("ByteOffset should be in range 0..=str length ($byteLen)",
                context:['str'=>$str,'skipChar'=>$skipChar,'byteOffset'=>$byteOffset]
            );
            }
            if(!self::isUtf8SafeAsciiChar($skipChar)){
                throw new InternalException("Skipchar should be ascii character.",
                context:['str'=>$str,'skipChar'=>$skipChar,'byteOffset'=>$byteOffset]
            );
            }
            $oldByteOffset = $byteOffset;
            for(;$byteOffset < $byteLen && $str[$byteOffset] === $skipChar;++$byteOffset);
            return $byteOffset - $oldByteOffset;
        }


        /**
         * @param string $str
         * @param int &$bytePos
         * @param int &$linePos
         * @param int &$columnPos
         * @return int
         * Returns count of skipped chars
         */
        public static function skipWhitesAndAdvancePos(string $str, int &$bytePos, int &$linePos, int &$columnPos,int $byteOffset = 0):int
        {
            $skippedCount = 0;
            $start = $byteOffset;
            foreach (self::iterateOverString($str,byteOffset:$byteOffset) as $end => $char) {
                $newLine = $linePos;
                $newCol = $columnPos + 1;
                if ($char === "\n") {
                    $newLine += 1;
                    $newCol = 1;
                }
                if (!self::isTrimmableWhite($char)){
                    break;
                }
                $linePos = $newLine;
                $columnPos = $newCol;
                // end - start = character length
                $bytePos += $end - $start;
                $skippedCount+=1;
                $start = $end;
            }
            return $skippedCount;
        }
    }
}
