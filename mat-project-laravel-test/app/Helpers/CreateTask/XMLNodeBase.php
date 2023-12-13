<?php

namespace App\Helpers\CreateTask{

    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Exceptions\XMLInvalidAttributeException;
    use App\Exceptions\XMLInvalidAttributeValueException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLMissingRequiredAttributesException;
    use App\Types\Coords;
    use App\Utils\Utils;

    abstract class XMLNodeBase{

        protected readonly ?XMLNodeBase $parent;
        protected readonly string $name;
        /**
         * @var array<string,XMLNodeBase> $children
         */
        protected array $children;

        /**
         * @param string $name
         * @param array<string,XMLNodeBase> $children
         * @param ?XMLNodeBase $parent
         */
        protected function __construct(string $name,array $children,?XMLNodeBase $parent = null){
            $this->parent=$parent;
        if(!$name) throw new InternalException("XMLNode must have a name");
            $this->name = $name;
            $this->children = [];
            foreach($children as $child){
                $childName = $child->name;
                if(array_key_exists($childName,$this->children)){
                    throw new InvalidArgumentException("children",$children,"children should have unique names");
                }
            }
        }

        /**
         * @param string $value
         * @param TaskRes $taskRes
         * @param callable():array{column:int,line:int,byteIndex:int}
         */
        public abstract function appendValue(string $value,TaskRes $taskRes,callable $getParserPosition):void;
        /**
         * @return string[]
         */
        protected function getChildrenNames():array{
            return array_keys($this->children);
        }

        /**
         * @param string $name
         * @param callable():array{column:int,line:int,byteIndex:int}
         */
        public function getChild(string $name,callable $getParserPosition):XMLNodeBase{
            $child = $this->children[$name] ?? false;
            if($child === false){
             $this->invalidElement($name,$getParserPosition());
            }
            return $child;
        }

        /**
         * @return string[]
         */
        public function getXMLPath(bool $includeThis = true):array{
            $path = [];
            $node = $this;
            if(!$includeThis){
                $node = $node->parent;
            }
            for(;$node->parent;$node = $node->parent){
                array_unshift($path,$node->name);
            }
            return $path;
        }


        /**
         * @param iterable<string,string> $attributes
         * @param TaskRes $taskRes
         * @param string $name
         * @return void
         */
        public function validateStart(
            iterable $attributes,
            TaskRes $taskRes,
            ?string $name = null
            ):void{
            if($name !== null && $name !== $this->name){
                $this->invalidElement($name,[],thisElement:true);
            }
            $this->handleAttributes($attributes,$taskRes);
        }

         /**
         * @param TaskRes $taskRes
         * @return void
         */
        public abstract function validate(TaskRes $taskRes):void;

        /**
        * @return array{string,callable(string,TaskRes):void}
        */
       protected abstract function getRequiredAttributes():array;

       /**
        * @return array{string,callable(string,TaskRes):void}
        */
       protected abstract function getNonRequiredAttributes():array;



        protected function valueNotSupported():void{
            throw new XMLInvalidElementValueException(
                xpath: $this->getXMLPath(),
                message:"Element ".$this->name." does not support value."
             );
        }

        protected function invalidValue(string $description,Coords $coords){
            throw new XMLInvalidElementValueException(
               xpath: $this->getXMLPath(),
                description:$description,
                elementValueCoords:$coords,
            );
        }

        protected function invalidAttributeValue(string $attribute,string $description){
            throw new XMLInvalidAttributeValueException(
               xpath: $this->getXMLPath(),
                description:$description,
                attribute:$attribute,
            );
        }

        /**
         * @param string $attribute
         * @param array $allowedValues
         */
        protected function invalidEnumAttributeValue(string $attribute,array $allowedValues){
         $this->invalidAttributeValue($attribute,
         "Expected one of: ". Utils::arrayToStr($allowedValues)."."
        );
        }

        /**
         * @param string $name
         * @param array{column:int,line:int,byteIndex:int} $position
         */
        protected function invalidElement(string $name,array $position,bool $thisElement = false){
            $path = $this->getXMLPath(includeThis:!$thisElement);
            $description = "";
            $name = $this->name;
            $children = $this->getChildrenNames();
            if($thisElement){
                $description = "Expected element with name '{$name}'.";
            }
            else{
                if($children){
                $description = "Expected one of these elements: '"
                .Utils::arrayToStr($children)
                .".";
                }
                else{
                    $description = "Element '{$name}' does not have any children.";
                }
            }

            throw new XMLInvalidElementException(
                $path,
                element:$name,
                description:$description,
            );
        }

        /**
         * @param string $attribute
         * @return void
         * @throws XMLInvalidElementException
         */
        protected function invalidAttribute(string $attribute){
            $path = $this->getXMLPath();
            throw new XMLInvalidAttributeException(
                xpath:$path,
                attribute:$attribute,
            );
        }

        /**
         * @param string[] &$missingAttributes
         * @return void
         */
        protected function missingRequiredAttributes(array $missingAttributes){
            $path = $this->getXMLPath();
            throw new XMLMissingRequiredAttributesException(
                xpath:$path,
                missingRequiredAttributes:$missingAttributes
            );
        }

        /**
         * @param iterable<string,string> $attributes
         * @param TaskRes $taskRes
         */
        private function handleAttributes(iterable $attributes,TaskRes $taskRes){
            $requiredAttrs = $this->getRequiredAttributes();
            $nonRequiredAttrs = $this->getNonRequiredAttributes();
            foreach($attributes as $attribute => $value){
                $possibleAttrs = &$requiredAttrs;

                $trFunc = $requiredAttrs[$attribute]
                 ?? ($possibleAttrs = &$nonRequiredAttrs)[$attribute]
                  ?? false;

                if($trFunc === false){
                   $this->invalidAttribute($attribute);
                }
                unset($possibleAttrs[$attribute]);
                $trFunc($value,$taskRes);
            }
            if($requiredAttrs){
                $this->missingRequiredAttributes($requiredAttrs);
            }
        }
    }
}