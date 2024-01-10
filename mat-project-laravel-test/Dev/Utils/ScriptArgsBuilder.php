<?php
namespace Dev\Utils {

    use Exception;
    use Illuminate\Support\Str;

    class ScriptArgsBuilder{


        private array $flagOptionToVarMap = [];
        private array $valueOptionToVarMap = [];
    
        private array $longOptions = ["help"];
        private string $shortOptions = "h";
    
        private array $passedOptions = [];
        private array $passedValidOptions = [];
        private bool $invalidOptions = false;
        private array $arguments = [];

        private bool $help = false;
    
        public function __construct(){
        }
    
        public static function create(){
            return new self();
        }

        public function helpRequested(){
            return $this->help;
        }
    
        /**
         * @param string $name
         * @param false &$var
         * @param bool $shortOption
         */
        public function flag(string $name,false &$var,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

           if($shortOption){
            $this->flagOptionToVarMap[$name]=&$var;
            $this->shortOptions .= $name;
           }
           else{
            $this->flagOptionToVarMap[$name]=&$var;
            $this->longOptions[]=$name;
           }
           return $this;
        }
    
        /**
         * @param string $name
         * @param false &$var
         * @param bool $shortOption
         * @param callable(string):void $transformValue
         */
        public function option(string $name,string &$var,bool $shortOption = false,callable $transformValue=null){
            self::checkOptionName($name,$shortOption);
            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_OPTIONAL);
            if($shortOption){
                $this->valueOptionToVarMap[$name]=&$var;
                $this->shortOptions .= $argName;
            }
            else{
                $this->valueOptionToVarMap[$name]=&$var;
                $this->longOptions[]=$argName;
            }
            return $this;
         }

          /**
         * @param string $name
         * @param callable(string):void $set
         * @param bool $shortOption
         */
        public function optionSet(string $name,callable $set,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_OPTIONAL);
            if($shortOption){
                $this->valueOptionToVarMap[$name]=$set;
                $this->shortOptions .= $argName;
            }
            else{
                $this->valueOptionToVarMap[$name]=$set;
                $this->longOptions[]=$argName;
            }
            return $this;
         }

         public function requiresValueOption(string $name,null &$var,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_REQUIRED);
            if($shortOption){
                $this->valueOptionToVarMap[$name]=&$var;
                $this->shortOptions .= $argName;
            }
            else{
                $this->valueOptionToVarMap[$name]=&$var;
                $this->longOptions[]=$argName;
            }
            return $this;
         }
    
         public function getArguments(&$arguments){
            $arguments = $this->arguments;
            return $this;
         }
    
         public function showInvalidOptions(){
            if($this->invalidOptions){
            echo "\nInvalid options specified.\n",
            "Options specified: ";
            var_dump($this->passedOptions);
            echo "\n";
            }
            return $this;
         }
    
         public function showNoArguments(){
            if($this->arguments){
                echo "\nThis script does not support any arguments.",
                "\nArguments given: ",implode(", ",$this->arguments),
                "\n\n";
                }
                return $this;
         }

         public function showPassedOptions(){
            echo "\nValid passed options: ";
            var_dump($this->passedValidOptions);
            echo "\n";
            return $this;
         }

        /**
         * @return ScriptOption[]
         */
         private function decodeOptions():array{
           $soptions = $this->shortOptions;
           $loptions = $this->longOptions;
            $splittedSoptions = mb_str_split($soptions);
            $decodedOptions = [];

           while(($soption = array_shift($splittedSoptions)) === null){
            $type = "";
            if(($splittedSoptions[0] ?? null) === ':'){
                $type.=array_shift($splittedSoptions);
                if(($splittedSoptions[0] ?? null) === ':'){
                    $type.=array_shift($splittedSoptions);
                }
            }
            $decodedOptions[]=new ScriptOption($soption,ScriptOptionType::from($type));
           }
           foreach($loptions as $loption){
            $decodedOptions[]=ScriptOption::fromSpecialName($loption);
           }
    return $decodedOptions;
         }

         /**
          * @param ScriptOptionType $type
          * @param int[] $nums
          */
         private static function scriptOptionTypeToNum(ScriptOptionType $type,int $valueRequired=0,int $valueOptional=1,int $flag=2):int{
            
            return match($type){
                ScriptOptionType::VALUE_REQUIRED => $valueRequired,
                ScriptOptionType::VALUE_OPTIONAL => $valueOptional,
                ScriptOptionType::FLAG => $flag,
            };
         }

         

         private function showHelp(){
            
            $options = $this->decodeOptions();
            
            usort($options, fn(ScriptOption $value1,ScriptOption $value2)=>
            self::scriptOptionTypeToNum($value1->type)-self::scriptOptionTypeToNum($value2->type));

            $prevType = null;
            
            foreach($options as $option){
                if($prevType !== $option->type){
                    $prevType = $option->type;
                    echo "\n---",
                    $option->type->name,
                    "--------\n";
                }
                echo "  ",($option->isShort ? " -" : "--"),
                 " ",$option->name,"\n";
            }
         }
    
         public function fetchScriptArguments(array|null &$arguments = null){
            $restIndex = -1;
            $validOptions = getopt($this->shortOptions,$this->longOptions,$restIndex);
            $validOptionsCount = 0;
            $argv = $_SERVER["argv"];
            
            if ($validOptions) {
                if(($validOptions['help'] ?? $validOptions['h']  ?? null) !== null){
                    echo "\n---------HELP------------\n";
                    $this->help = true;
                    $this->showHelp();
                }
                $validOptionsCount = count($validOptions);
                foreach($validOptions as $argumentName => $argumentValue){
                    if($argumentValue === false){
                        $this->flagOptionToVarMap[$argumentName] = true;
                    }
                    else{
                        $set = &$this->valueOptionToVarMap[$argumentName];
                        if(is_callable($set)){
                            $set($argumentValue);
                        }
                        else{
                        $set = $argumentValue;
                        }
                    }
                }
            }
            $invalidOptionOffset =$validOptionsCount+1;
            $this->passedOptions =$argv;
            $this->passedValidOptions = $validOptions;
           $this->invalidOptions = $restIndex-$invalidOptionOffset > 0;
           $this->arguments = $arguments = array_slice($argv,$restIndex);
           return $this;
         }

         private static function checkOptionName(string $name,bool $isShort){
            if($isShort){
                self::checkShortOptionName($name);
            }
            else{
                self::checkLongOptionName($name);
            }
         }

         private static function checkLongOptionName(string $name){
            if(strlen($name) === 0){
                throw new Exception("Long options cannot be empty strings.");
            }
         }
    
         private static function checkShortOptionName(string $name){
            if(mb_strlen($name) !== 1){
                throw new Exception("Short options should have only one character name\nPassed name: '$name'.");
            }
         }
    }
}