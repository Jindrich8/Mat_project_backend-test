<?php
namespace Dev\DtoGen {

    use Illuminate\Support\Str;

    class StrUtils
    {
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
        public static function explode(string $delimiter,string $string,int $limit = -1,bool $ignoreEmptyParts = true):array|false{
           $delimiter = preg_quote($delimiter,'/');
           $flags = 0;
           if($ignoreEmptyParts){
            $flags |= PREG_SPLIT_NO_EMPTY;
           }
            return preg_split("/$delimiter/u",$string,$limit,$flags);
        }

         /**
         * @param string[] $delimiters
         * @param string $string
         * @param string $limit
         * @param bool $ignoreEmptyParts
         * @return string[]|false
         * an exploded array or FALSE if an error occurred.
         */
        public static function explodeByArray(array $delimiters,string $string,int $limit = -1,bool $ignoreEmptyParts = true):array|false{
            $delimiters = array_map(fn($del)=>preg_quote($del,'/'),$delimiters);
            $regex = '('.implode("|",$delimiters).')';
            $flags = 0;
            if($ignoreEmptyParts){
             $flags |= PREG_SPLIT_NO_EMPTY;
            }
             return preg_split("/$regex/u",$string,$limit,$flags);
         }

        /**
         * @param string $search
         * @param string $replace
         * @param string $subject
         * @return string|null
         * returns string with replaced parts or null if error occurred
         */
        public static function replace(string $search,string $replace,string $subject):string|null{
            $search = preg_quote($search,'/');
             return preg_replace("/$search/u",$replace,$subject);
         }

        
    }
}
