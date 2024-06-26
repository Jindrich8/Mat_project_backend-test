<?php
namespace Dev\Utils {

    use Exception;

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
         * @return ScriptArgsBuilder
         */
        public function flag(string $name,bool &$var,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

            $this->flagOptionToVarMap[$name]=&$var;
            if($shortOption){
                $this->shortOptions .= $name;
           }
           else{
               $this->longOptions[]=$name;
           }
           return $this;
        }

        /**
         * @param string $name
         * @param string $var
         * @param bool $shortOption
         * @param callable|null $transformValue
         * @return ScriptArgsBuilder
         */
        public function option(string $name,string &$var,bool $shortOption = false,callable $transformValue=null){
            self::checkOptionName($name,$shortOption);
            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_OPTIONAL);
            $this->valueOptionToVarMap[$name]=&$var;
            if($shortOption){
                $this->shortOptions .= $argName;
            }
            else{
                $this->longOptions[]=$argName;
            }
            return $this;
         }

        /**
         * @param string $name
         * @param callable(string):void $set
         * @param bool $shortOption
         * @return ScriptArgsBuilder
         */
        public function optionSet(string $name,callable $set,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_OPTIONAL);
            $this->valueOptionToVarMap[$name]=$set;
            if($shortOption){
                $this->shortOptions .= $argName;
            }
            else{
                $this->longOptions[]=$argName;
            }
            return $this;
         }

         public function requiresValueOption(string $name,mixed &$var,bool $shortOption = false){
            self::checkOptionName($name,$shortOption);

            $argName = ScriptOption::transformToSpecialName($name,ScriptOptionType::VALUE_REQUIRED);
             $this->valueOptionToVarMap[$name]=&$var;
             if($shortOption){
                 $this->shortOptions .= $argName;
            }
            else{
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

         public function showPassedOptions(): static
         {
            echo "This: ";
            var_dump($this);
            echo "\nValid passed options: ";
            var_dump($this->passedValidOptions);
            echo "\n";
            return $this;
         }

        /**
         * @return ScriptOption[]
         * @throws Exception
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
         * @param int $valueRequired
         * @param int $valueOptional
         * @param int $flag
         * @return int
         */
         private static function scriptOptionTypeToNum(ScriptOptionType $type,int $valueRequired=0,int $valueOptional=1,int $flag=2):int{

            return match($type){
                ScriptOptionType::VALUE_REQUIRED => $valueRequired,
                ScriptOptionType::VALUE_OPTIONAL => $valueOptional,
                ScriptOptionType::FLAG => $flag,
            };
         }


        /**
         * @throws Exception
         */
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
                        $validOptions[$argumentName] = true;
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

        /**
         * @throws Exception
         */
        private static function checkOptionName(string $name, bool $isShort){
            if($isShort){
                self::checkShortOptionName($name);
            }
            else{
                self::checkLongOptionName($name);
            }
         }

        /**
         * @throws Exception
         */
        private static function checkLongOptionName(string $name){
            if(strlen($name) === 0){
                throw new Exception("Long options cannot be empty strings.");
            }
         }

        /**
         * @throws Exception
         */
        private static function checkShortOptionName(string $name){
            if(mb_strlen($name) !== 1){
                throw new Exception("Short options should have only one character name\nPassed name: '$name'.");
            }
         }
    }
}
