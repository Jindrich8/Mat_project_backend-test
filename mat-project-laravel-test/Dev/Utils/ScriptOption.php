<?php
namespace Dev\Utils {

    use Exception;
    use Illuminate\Support\Str;

    class ScriptOption{
        
        public readonly bool $isShort;

        public function __construct(public readonly string $name,public readonly ScriptOptionType $type){
            if(strlen($name) === 0){
                throw new Exception("Option name must be a non-empty string.");
            }
            $this->isShort = mb_strlen($name) === 1;
        }

        public static function fromSpecialName(string $specialName):self{
           $name = Str::before($specialName,':');
           $type =mb_strcut($specialName,mb_strlen($name));
           return new self($name,ScriptOptionType::from($type));
        }

        public static function transformToSpecialName(string $name, ScriptOptionType $type){
            return $name . $type->value;
        }

        public function getSpecialName():string{
            return self::transformToSpecialName($this->name,$this->type);
        }
    }
}