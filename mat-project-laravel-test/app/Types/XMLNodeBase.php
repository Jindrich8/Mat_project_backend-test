<?php

namespace App\Types {

    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttributeErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttributeValueErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValueErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValuePartErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredAttributesErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredElementsErrorData;
    use App\Exceptions\InternalException;
    use App\Exceptions\XMLInvalidAttributeException;
    use App\Exceptions\XMLInvalidAttributeValueException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLInvalidElementValuePartException;
    use App\Exceptions\XMLMissingRequiredAttributesException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\Types\GetXMLParserPosition;
    use App\Types\XMLAttributes;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Utils\StrUtils;
    use App\Utils\Utils;
    use App\Types\XMLContextWOffset;
    use App\Types\XMLSimpleContext;

    abstract class XMLNodeBase
    {

        protected string $name;
        protected ?XMLChildren $children;
        protected ?XMLAttributes $attributes;

        private int $count;
        private readonly int $maxCount;
        private readonly bool $shouldHaveAtLeastOneChild;
        private ?XMLValidParserPosition $elementStartPos;
        private bool $hasStartPos;

        /**
         * This method may drop all informations about it's self
         * @return XMLNodeBase
         * Returns parent node
         */
        protected abstract function moveUp(XMLContextBase $context):?XMLNodeBase;

       protected abstract function getParentName():?string;

        public function getName():string{
            return $this->name;
        }

      
        /**
         * This method exists for determination of nodes with same parent node
         */
        public abstract function getParentObjectId():?object;

        
        

        /**
         * @param string $name
         * @param ?XMLAttributes $attributes
         * @param bool $shouldHaveAtLeastOneChild
         * @param int $maxCount
         */
        protected function __construct(string $name,?XMLAttributes $attributes = null,bool $shouldHaveAtLeastOneChild = false,int $maxCount = PHP_INT_MAX)
        {
            if (!$name) throw new InternalException("XMLNode must have a name");
            $this->name = $name;
            $this->children = null;
            $this->attributes = $attributes;
            $this->maxCount = $maxCount;
            $this->shouldHaveAtLeastOneChild = $shouldHaveAtLeastOneChild;
            $this->elementStartPos = null;
            $this->reset();
        }

        protected function setChildren(XMLChildren $children):void{
            $this->children = $children;
        }

        public function reset(){
            $this->count = 0;
            $this->hasStartPos = false;
        }

        /**
         * @param string $value
         * @param XMLContextBase $context
         * @return void
         */
        public abstract function appendValue(string $value, XMLContextBase $context): void;

        /**
         * @return string[]
         */
        protected function getChildrenNames(): array
        {
            return $this->children->getNames();
        }




        public function getChild(string $name, GetXMLParserPosition $getParserPosition): XMLNodeBase
        {
            $child = $this->children->tryGetChild($name);
            if ($child === false) {
                echo "\nCHILD '$name' NOT FOUND IN {$this->name} - THIS: ";
                var_dump($this);
                echo "\n";
                $this->invalidElement($getParserPosition,elementName:$name);
            }
            return $child;
        }

        /**
         * @param iterable<string,string> $attributes
         * @param XMLContextBase $context
         * @param string $name
         * @return void
         */
        public function validateStart(
            iterable $attributes,
            XMLContextBase $context,
            ?string $name = null
        ): void {
            if ($name !== null && $name !== $this->name) {
                $this->invalidElement($context);
            }
             if(++$this->count > $this->maxCount){
                $this->tooManyElements($context,$this->maxCount);
            }
            $this->handleAttributes($attributes, $context);
            dump("validateStart - {$this->name}");
            $this->elementStartPos ??= new XMLValidParserPosition();
            $this->elementStartPos->setPosFromProvider($context);
            $this->hasStartPos = true;
        }

        /**
         * @param XMLContextBase $context
         * @return void
         */
        protected function validate(XMLContextBase $context): void{
            if($this->children){
                $missing = [];
                $childrenCount = 0;
                foreach($this->children->getChildren() as $name => list($child,$required)){
                    if($child->count !== 0){
                        ++$childrenCount;
                    }
                    else if($required){
                        $missing[]=$name;
                    }
                    $child->reset();
                }
            if($missing){
                $this->missingRequiredElements($missing,$context);
            }
            else if($this->shouldHaveAtLeastOneChild && $childrenCount === 0){
                    $this->missingRequiredElements([$this->getChildrenNames()],$context);
            }
        }
        }

        protected function getStartPos():GetXMLParserPosition{
            $startPos = $this->elementStartPos;
            if(!$this->hasStartPos || !$startPos){
                throw new InternalException("Could not get start position, when validateStart was not called!",
                context:['this' => $this]);
            }
            return $startPos;
        }

        public function validateAndMoveUp(XMLContextBase $context):?XMLNodeBase{
            $this->validate($context);
            return $this->moveUp($context);
        }


        /**
         * @param iterable<string,string> $attributes
         * @param XMLContextBase $context
         */
        private function handleAttributes(iterable $attributes, XMLContextBase $context)
        {
            $nodeAttributes = $this->attributes;
            $usedRequiredAttributes = [];
            $usedNonRequiredAttributes = [];
            foreach ($attributes as $attribute => $value) {
                $parseAndRequiredOrFalse = $nodeAttributes?->tryGetAttribute($attribute) ?? false;

                if ($parseAndRequiredOrFalse === false) {
                    $this->invalidAttribute($attribute, $context);
                }
                if(array_key_exists($attribute,$usedRequiredAttributes) 
                || array_key_exists($attribute,$usedNonRequiredAttributes)){
                    $this->duplicateAttribute($attribute,$context);
                }

                list($parse,$required) = $parseAndRequiredOrFalse;
                if($required){
                $usedRequiredAttributes[$attribute] = true;
                }
                else{
                    $usedNonRequiredAttributes[$attribute] = true;
                }
                $parse($this,$value,$context);
            }
            unset($usedNonRequiredAttributes);
            if ($nodeAttributes !== null && count($usedRequiredAttributes) < $nodeAttributes->getNumOfRequiredAttributes()) {
                $missingAttributes = [];
                foreach($nodeAttributes->getRequiredAttributes() as $name => $value){
                    if(!array_key_exists($name,$usedRequiredAttributes)){
                        $missingAttributes[]=$name;
                    }
                }
                unset($usedRequiredAttributes);
                $this->missingRequiredAttributes($missingAttributes, $context);
            }
        }

        protected function valueNotSupported(): void
        {
            $this->invalidValue(
                message: "Element '{$this->name}' does not support any value",
                description:''
            );
        }

        protected function tooManyElements(GetXMLParserPosition $getPosCallback,?int $maximum = null,?int $specified = null,string $description = ''): void{
            if($maximum === null && $specified !== null){
                $maximum = $specified - 1;
            }
            $this->invalidElement(
                getPosCallback:$getPosCallback,
            message:"Too many '{$this->name}' elements",
            description:"Maximum number of '{$this->name}' elements is $maximum"
            .($specified !== null ? ", but $specified elements specified":"")
        );
        }

        protected function invalidValue(
            string $description = '',
            string $message = ''
        ) {
            $this->getStartPos()->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
         
            throw new XMLInvalidElementValueException(
                element: $this->name,
                errorData: XMLInvalidElementValueErrorData::create()
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex),
                message: $message,
                description: $description
            );
        }

        protected function invalidValuePart(
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength,
            string $description = '',
            string $message = ''
        ) {
            if(!XMLValidParserPosition::isPosValid(
                column:$column,
                line:$line,
                byteIndex:$byteIndex
            ) || $byteLength <= 0){
                throw new InternalException(
                    message:$byteLength<=0?"Length should be positive.":"Invalid position offset!",
                context:[
                    'column'=>$column,
                    'line'=>$line,
                    'byteIndex'=>$byteIndex,
                    'description'=>$description,
                    'message'=>$message,
                    'node'=>$this
                ]);
            }
            
         
            throw new XMLInvalidElementValuePartException(
                element: $this->name,
                errorData: XMLInvalidElementValuePartErrorData::create()
                    ->setColumn($column)
                    ->setLine($line)
                    ->setByteIndex($byteIndex)
                    ->setByteLength($byteLength),
                message: $message,
                description: $description
            );
        }

        protected function valueShouldNotBeEmpty(
        string $description,
        string $message = ''){
            $this->invalidValue(
            message:$message ? $message : "Element '{$this->name}' value should not be empty",
        description:$description
    );  
        }

        protected function invalidAttributeValue(string $attribute, string $description, GetXMLParserPosition $getPosCallback)
        {
            $getPosCallback->getPos($column, $line, $byteIndex);
            echo "\nGET POS CALLBACK: column: $column, line: $line, byteIndex: $byteIndex: ";
            var_dump($getPosCallback);
            echo "\n";
            throw new XMLInvalidAttributeValueException(
                element: $this->name,
                errorData: XMLInvalidAttributeValueErrorData::create()
                ->setInvalidAttribute($attribute)
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex),
                description: $description
            );
        }

        /**
         * @param string $attribute
         * @param string[] $allowedValues
         * @param GetXMLParserPosition $getPosCallback
         */
        protected function invalidEnumAttributeValue(string $attribute, array $allowedValues, GetXMLParserPosition $getPosCallback)
        {
            $this->invalidAttributeValue(
                attribute: $attribute,
                description: "Expected one of: " . Utils::arrayToStr($allowedValues) . ".",
                getPosCallback: $getPosCallback
            );
        }


        protected function invalidElement(GetXMLParserPosition $getPosCallback,?string $elementName = null,string $message = '', string $description = '')
        {
            $name = $this->name;
            if(!$description){
            $description = "";
            if ($elementName === null) {
                $description = "Expected element with name '{$name}'.";
            } else {
                $children = $this->getChildrenNames();
                if ($children) {
                    $description = "Expected one of these elements: "
                        . Utils::arrayToStr($children)
                        . ".";
                } else {
                    $description = "Element '{$name}' does not have any children.";
                }
            }
        }
            $getPosCallback->getPos($column, $line, $byteIndex);

            throw new XMLInvalidElementException(
                element: $elementName ?? $name,
                parent: $this->getParentName(),
                message:$message,
                description: $description,
                errorData: XMLInvalidElementErrorData::create()
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex)
            );
        }

        /**
         * @param string $attribute
         * @param GetXMLParserPosition $getPosCallback
         * @return void
         * @throws XMLInvalidAttributeException
         */
        protected function invalidAttribute(string $attribute, GetXMLParserPosition $getPosCallback)
        {
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
            throw new XMLInvalidAttributeException(
                element: $this->name,
                errorData: XMLInvalidAttributeErrorData::create()
                ->setInvalidAttribute($attribute)
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex)
            );
        }

        protected function duplicateAttribute(string $attribute, GetXMLParserPosition $getPosCallback){
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
            throw new XMLInvalidAttributeException(
                element: $this->name,
                errorData: XMLInvalidAttributeErrorData::create()
                ->setInvalidAttribute($attribute)
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex),
                    message:"Attribute '$attribute' should be specified only once"
            );
        }

        /**
         * @param string[] &$missingAttributes
         * @param GetXMLParserPosition $getPosCallback
         * @return void
         */
        protected function missingRequiredAttributes(array $missingAttributes, GetXMLParserPosition $getPosCallback)
        {
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            throw new XMLMissingRequiredAttributesException(
                element: $this->name,
                missingRequiredAttributes: $missingAttributes,
                errorData: XMLMissingRequiredAttributesErrorData::create()
                    ->setELine($line)
                    ->setEColumn($column)
                    ->setEByteIndex($byteIndex)
            );
        }

        /**
         * @param array<string|array<string>> $missingRequiredElements
     * if array element is array, then it means, that it should be one of element array elements
     * @param GetXMLParserPosition $getPosCallback
         */
        protected function missingRequiredElements(array $missingElements, GetXMLParserPosition $getPosCallback){
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            throw new XMLMissingRequiredElementsException(
                element: $this->name,
                missingRequiredElements: $missingElements,
                errorData: XMLMissingRequiredElementsErrorData::create()
                    ->setELine($line)
                    ->setEColumn($column)
                    ->setEByteIndex($byteIndex)
            );
        }
    }
}
