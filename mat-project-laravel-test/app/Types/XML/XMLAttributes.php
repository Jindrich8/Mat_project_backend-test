<?php

namespace App\Types\XML {

    use App\Exceptions\InvalidArgumentException;

    class XMLAttributes
    {
        /**
         * @var array<string,callable(XMLNodeBase $node, string $value,XMLContextBase $context):void> $nonRequiredAttributes
         */
        private array $nonRequiredAttributes;
        /**
         * @var array<string,callable(XMLNodeBase $node, string $value, XMLContextBase $context):void> $requiredAttributes
         */
        private array $requiredAttributes;

        public function __construct()
        {
            $this->nonRequiredAttributes = [];
            $this->requiredAttributes = [];
        }

        public static function construct():self{
            return new self();
        }

        public function getNumOfRequiredAttributes():int{
            return count($this->requiredAttributes);
        }

        public function getNumOfNonRequiredAttributes():int{
            return count($this->nonRequiredAttributes);
        }

        public function getNumOfAllAttributes():int{
            return $this->getNumOfRequiredAttributes() + $this->getNumOfNonRequiredAttributes();
        }

        public function getNumOfAttributes(bool $required):int{
            return $required ? $this->getNumOfRequiredAttributes() : $this->getNumOfNonRequiredAttributes();
        }

        /**
         * @return iterable<string,callable(XMLNodeBase $node, string $value,XMLContextBase $context):void>
         */
        public function getRequiredAttributes():iterable{
            foreach($this->requiredAttributes as $name => $value){
                yield $name=>$value;
            }
        }

        /**
         * @return iterable<string,callable(XMLNodeBase $node, string $value,XMLContextBase $context):void>
         */
        public function getNonRequiredAttributes():iterable{
            foreach($this->nonRequiredAttributes as $name => $value){
                yield $name=>$value;
            }
        }

        /**
         * @return iterable<string,callable(XMLNodeBase $node, string $value,XMLContextBase $context):void>
         */
        public function getAttributes(bool $required):iterable{
            return $required ? $this->getRequiredAttributes() : $this->getNonRequiredAttributes();
        }

        /**
         * @return iterable<string,callable(XMLContextBase $context):void>
         */
        public function getAllAttributes():iterable{
            foreach($this->requiredAttributes as $name => $value){
                yield $name=>$value;
            }
            foreach($this->nonRequiredAttributes as $name => $value){
                yield $name=>$value;
            }
        }

        /**
         * @param string $name
         * @param callable(XMLNodeBase $node, string $value, XMLContextBase $context):void $parse
         * @param bool $required
         * @return self
         */
        public function addAttribute(string $name, callable $parse,bool $required):self{
            $attributes = [];
            $attributesName = "";
            if($required){
                $attributes = &$this->requiredAttributes;
                $attributesName = "required";
            }
            else{
                $attributes = &$this->nonRequiredAttributes;
                $attributesName = "non required";
            }
            if(array_key_exists($name,$attributes)){
                throw new InvalidArgumentException("name",$name,"$attributesName attribute with same name already exists",context:[
                    'attributes' => $attributes,
                    'newAttribute' => [$name => $parse]
                ]);
            }
            $attributes[$name] = $parse;
            return $this;
        }


        /**
         * @param string $name
         * @return array{0:callable(XMLNodeBase $node, string $value,XMLContextBase $context):void,1:bool}|false
         */
        public function tryGetAttribute(string $name):array|false{
            $attributes = &$this->requiredAttributes;
            if(!array_key_exists($name,$attributes) &&
            !array_key_exists($name,($attributes = &$this->nonRequiredAttributes))){
                return false;
            }
            return [$attributes[$name],$attributes === $this->requiredAttributes];
        }


    }
}
