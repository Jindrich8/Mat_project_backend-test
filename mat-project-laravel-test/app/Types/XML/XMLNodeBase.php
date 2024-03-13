<?php

namespace App\Types\XML {

    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValueErrorData;
    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredAttributesErrorData;
    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValuePartErrorData;
    use App\Dtos\Defs\Errors\XML\XMLInvalidElementErrorData;
    use App\Dtos\Defs\Errors\XML\XMLInvalidAttributeValueErrorData;
    use App\Dtos\Defs\Errors\XML\XMLInvalidAttributeErrorData;
    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredElementsErrorData;
    use App\Exceptions\InternalException;
    use App\Exceptions\XMLInvalidAttributeException;
    use App\Exceptions\XMLInvalidAttributeValueException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLInvalidElementValuePartException;
    use App\Exceptions\XMLMissingRequiredAttributesException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\Utils\StrUtils;
    use App\Utils\Utils;
    use App\Utils\DebugLogger;
    use App\Dtos\Defs\Errors\XML\DefsOr;
    use App\Types\TrimTypeEnum;

    abstract class XMLNodeBase
    {

        protected string $name;
        protected ?XMLChildren $children;
        protected ?XMLAttributes $attributes;

        protected int $count;
        private readonly int $maxCount;
        private readonly bool $shouldHaveAtLeastOneChild;
        private ?XMLValidParserPosition $elementStartPos;
        private bool $hasStartPos;

        private bool $isValueNode;
        private ?XMLContextWOffset $tempContext;
        private bool $isFirstAppendValue;
        private bool $isTrimming;

        /**
         * This method may drop all informations about it's self
         * @return XMLNodeBase
         * Returns parent node
         */
        protected abstract function moveUp(XMLContextBase $context): ?XMLNodeBase;

        protected abstract function getParentName(): ?string;

        public function getName(): string
        {
            return $this->name;
        }


        /**
         * This method exists for determination of nodes with same parent node
         */
        public abstract function getParentObjectId(): ?object;


        protected function __construct(
            string $name,
            ?XMLAttributes $attributes = null,
            bool $shouldHaveAtLeastOneChild = false,
            int $maxCount = PHP_INT_MAX,
            bool $isValueNode = true
        ) {
            if (!$name) throw new InternalException("XMLNode must have a name");
            $this->name = $name;
            $this->children = null;
            $this->attributes = $attributes;
            $this->maxCount = $maxCount;
            $this->shouldHaveAtLeastOneChild = $shouldHaveAtLeastOneChild;
            $this->elementStartPos = null;
            $this->isValueNode = $isValueNode;
            $this->count = 0;
            $this->tempContext = null;
            $this->isTrimming = false;
            $this->isFirstAppendValue = false;
            $this->reset();
        }

        protected function setChildren(XMLChildren $children): void
        {
            $this->children = $children;
        }

        public function reset()
        {
            $this->count = 0;
            $this->hasStartPos = false;
            $this->tempContext = null;
            $this->isTrimming = false;
            $this->isFirstAppendValue = false;
        }



        /**
         * @param string $value
         * @param XMLContextBase $context
         * @return void
         */
        public function appendValuePart(string $value, XMLContextBase $context): void
        {
            if (!$this->isValueNode) {
                if (StrUtils::trimWhites($value, TrimTypeEnum::TRIM_BOTH)) {
                    $this->valueNotSupported();
                }
                return;
            }
            $columnOffset = 0;
            $lineOffset = 0;
            $byteOffset = 0;
            $trimmedCount = 0;
            $newLineChar = "\n";
            $lines = explode($newLineChar, $value);
            if (!$lines) {
                $lines = [$value];
            }
            $newValue = "";
            $first = array_shift($lines);
            if ($this->isFirstAppendValue) {
                $trimmedCount = 0;
                $trimmed = StrUtils::utf8LtrimWhites(
                    str: $first,
                    trimmedCount: $trimmedCount
                );
                $byteOffset += strlen($first) - strlen($trimmed);
                if ($trimmed === '') {
                    $first = array_shift($lines) ?? $first;
                    $byteOffset += strlen($newLineChar);
                    ++$lineOffset;
                    $columnOffset = 0;
                } else {
                    $columnOffset += $trimmedCount;
                }

                $this->isFirstAppendValue = false;
            }
            if ($this->isTrimming) {
                $trimmedCount = 0;
                $trimmed = StrUtils::utf8LtrimWhites(
                    str: $first,
                    trimmedCount: $trimmedCount
                );
                $columnOffset += $trimmedCount;
                $byteOffset += strlen($first) - strlen($trimmed);
                $first = $trimmed;
            }
            $newValue = $first;
            $lastLine = $first;
            while (($line = array_shift($lines)) !== null) {
                $line = StrUtils::utf8LtrimWhites(
                    str: $line,
                    trimmedCount: $trimmedCount
                );
                $newValue .= "\n" . $line;
                $lastLine = $line;
            }
            $this->isTrimming = $lastLine === '';

            $newContext = ($this->tempContext ??= new XMLContextWOffset($context, 0, 0, 0))
                ->update(
                    $context,
                    columnOffset: $columnOffset,
                    lineOffset: $lineOffset,
                    byteOffset: $byteOffset
                );

            DebugLogger::log("appendValuePart", [
                'value' => $value,
                'newValue' => $newValue
            ]);
            $this->appendValue($newValue, $newContext);
        }

        /**
         * @param string $value
         * @param XMLContextBase $context
         * @return void
         */
        protected function appendValue(string $value, XMLContextBase $context): void
        {
            if (!$this->isValueNode) {
                if (StrUtils::trimWhites($value, TrimTypeEnum::TRIM_BOTH)) {
                    $this->valueNotSupported();
                }
                return;
            }
        }

        /**
         * @return string[]
         */
        protected function getChildrenNames(): array
        {
            return $this->children->getNames();
        }


        /**
         * @throws XMLInvalidElementException
         */
        public function getChild(string $name, GetXMLParserPositionInterface $getParserPosition): XMLNodeBase
        {
            $child = $this->children->tryGetChild($name);
            if ($child === false) {
                DebugLogger::log("CHILD '$name' NOT FOUND IN {$this->name} - THIS", $this);
                $this->invalidElement($getParserPosition, elementName: $name);
            }
            return $child;
        }

        /**
         * @param iterable<string,string> $attributes
         * @param XMLContextBase $context
         * @param string|null $name
         * @return void
         * @throws XMLInvalidAttributeException
         * @throws XMLInvalidElementException
         * @throws XMLMissingRequiredAttributesException
         */
        public function validateStart(
            iterable $attributes,
            XMLContextBase $context,
            ?string $name = null
        ): void {
            if ($name !== null && $name !== $this->name) {
                $this->invalidElement($context, elementName: $name,isInvalidSelf:true);
            }
            if (++$this->count > $this->maxCount) {
                $this->tooManyElements($context, $this->maxCount);
            }
            DebugLogger::log("Add count - '".$this->getParentName()."'->'{$this->name}'",['count' => $this->count]);
            $this->handleAttributes($attributes, $context);
            // dump("validateStart - {$this->name}");
            $this->elementStartPos ??= new XMLValidParserPosition();
            $this->elementStartPos->setPosFromProvider($context);
            $this->elementStartPos->getPos($column, $line, $byteIndex);
            $this->isFirstAppendValue = true;
            $this->hasStartPos = true;
            $this->isTrimming = true;
        }

        /**
         * @param XMLContextBase $context
         * @return void
         * @throws XMLMissingRequiredElementsException
         */
        protected function validate(XMLContextBase $context): void
        {
            if ($this->children) {
                $missing = [];
                $childrenCount = 0;
                foreach ($this->children->getChildren() as $name => list($child, $required)) {
                    if ($child->count !== 0) {
                        ++$childrenCount;
                    } else if ($required) {
                        $missing[] = $name;
                    }
                    $child->reset();
                }
                if ($missing) {
                    $this->missingRequiredElements($missing, $context);
                } else if ($this->shouldHaveAtLeastOneChild && $childrenCount === 0) {
                    $this->missingRequiredElements(
                        [
                            DefsOr::create()->setOr($this->getChildrenNames())
                        ],
                        $context
                    );
                }
            }
        }

        protected function getStartPos(): GetXMLParserPositionInterface
        {
            $startPos = $this->elementStartPos;
            if (!$this->hasStartPos || !$startPos) {
                throw new InternalException(
                    "Could not get start position, when validateStart was not called!",
                    context: ['this' => $this]
                );
            }
            return $startPos;
        }

        /**
         * @throws XMLMissingRequiredElementsException
         */
        public function validateAndMoveUp(XMLContextBase $context): ?XMLNodeBase
        {
            $this->validate($context);
            // dump("'{$this->name}' moving up to '".$this->getParentName()."'.");
            return $this->moveUp($context);
        }

        private static function getExpectedAttributes(
            XMLAttributes $nodeAttributes,
            array $usedRequiredAttributes = [],
            array $usedNonRequiredAttributes = []
            ){
                $expectedAttrs = [];
                foreach($nodeAttributes->getRequiredAttributes() as $attr => $value){
                    if(!array_key_exists($attr,$usedRequiredAttributes)){
                        $expectedAttrs[]=$attr;
                    }
                }
                foreach($nodeAttributes->getNonRequiredAttributes() as $attr => $value){
                    if(!array_key_exists($attr,$usedNonRequiredAttributes)){
                        $expectedAttrs[]=$attr;
                    }
                }
                return $expectedAttrs;
        }


        /**
         * @param iterable<string,string> $attributes
         * @param XMLContextBase $context
         * @throws XMLInvalidAttributeException
         * @throws XMLMissingRequiredAttributesException
         */
        private function handleAttributes(iterable $attributes, XMLContextBase $context)
        {
            $nodeAttributes = $this->attributes;
            $usedRequiredAttributes = [];
            $usedNonRequiredAttributes = [];
            foreach ($attributes as $attribute => $value) {
                $parseAndRequiredOrFalse = $nodeAttributes?->tryGetAttribute($attribute) ?? false;

                if ($parseAndRequiredOrFalse === false) {
                    $this->invalidAttribute(
                        attribute: $attribute,
                        expectedAttributes: self::getExpectedAttributes(
                            nodeAttributes: $nodeAttributes,
                            usedRequiredAttributes: $usedRequiredAttributes,
                            usedNonRequiredAttributes: $usedNonRequiredAttributes
                        ),
                        getPosCallback: $context
                    );
                }
                if (
                    array_key_exists($attribute, $usedRequiredAttributes)
                    || array_key_exists($attribute, $usedNonRequiredAttributes)
                ) {
                    $this->duplicateAttribute(
                        attribute:$attribute,
                        expectedAttributes: self::getExpectedAttributes(
                            nodeAttributes: $nodeAttributes,
                            usedRequiredAttributes: $usedRequiredAttributes,
                            usedNonRequiredAttributes: $usedNonRequiredAttributes
                        ),
                        getPosCallback: $context
                    );
                }

                list($parse, $required) = $parseAndRequiredOrFalse;
                if ($required) {
                    $usedRequiredAttributes[$attribute] = true;
                } else {
                    $usedNonRequiredAttributes[$attribute] = true;
                }
                $parse($this, $value, $context);
            }
            unset($usedNonRequiredAttributes);
            if ($nodeAttributes !== null && count($usedRequiredAttributes) < $nodeAttributes->getNumOfRequiredAttributes()) {
                $missingAttributes = [];
                foreach ($nodeAttributes->getRequiredAttributes() as $name => $value) {
                    if (!array_key_exists($name, $usedRequiredAttributes)) {
                        $missingAttributes[] = $name;
                    }
                }
                unset($usedRequiredAttributes);
                $this->missingRequiredAttributes($missingAttributes, $context);
            }
        }

        /**
         * @throws XMLInvalidElementValueException
         */
        protected function valueNotSupported(): void
        {
            $this->invalidValue(
                description: '',
                message: "Element '{$this->name}' does not support any value"
            );
        }

        /**
         * @throws XMLInvalidElementException
         */
        protected function tooManyElements(GetXMLParserPositionInterface $getPosCallback, ?int $maximum = null, ?int $specified = null, string $description = ''): void
        {
            if ($maximum === null && $specified !== null) {
                $maximum = $specified - 1;
            }
            $this->invalidElement(
                getPosCallback: $getPosCallback,
                message: "Too many '{$this->name}' elements",
                description: "Maximum number of '{$this->name}' elements is $maximum"
                    . ($specified !== null ? ", but $specified elements specified" : "")
            );
        }

        /**
         * @throws XMLInvalidElementValueException
         */
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
            if (!XMLValidParserPosition::isPosValid(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            ) || $byteLength <= 0) {
                throw new InternalException(
                    message: $byteLength <= 0 ? "Length should be positive." : "Invalid position offset!",
                    context: [
                        'column' => $column,
                        'line' => $line,
                        'byteIndex' => $byteIndex,
                        'description' => $description,
                        'message' => $message,
                        'node' => $this
                    ]
                );
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

        /**
         * @throws XMLInvalidElementValueException
         */
        protected function valueShouldNotBeEmpty(
            string $description,
            string $message = ''
        ) {
            $this->invalidValue(
                description: $description,
                message: $message ?: "Element '{$this->name}' value should not be empty"
            );
        }

        /**
         * @throws XMLInvalidAttributeValueException
         */
        protected function invalidAttributeValue(string $attribute, string $description, GetXMLParserPositionInterface $getPosCallback)
        {
            $getPosCallback->getPos($column, $line, $byteIndex);
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
         * @param GetXMLParserPositionInterface $getPosCallback
         * @throws XMLInvalidAttributeValueException
         */
        protected function invalidEnumAttributeValue(string $attribute, array $allowedValues, GetXMLParserPositionInterface $getPosCallback)
        {
            $this->invalidAttributeValue(
                attribute: $attribute,
                description: "Expected one of: " . Utils::arrayToStr($allowedValues) . ".",
                getPosCallback: $getPosCallback
            );
        }


        /**
         * @throws XMLInvalidElementException
         */
        protected function invalidElement(GetXMLParserPositionInterface $getPosCallback, ?string $elementName = null, string $message = '', string $description = '', bool $isInvalidSelf = false)
        {
            $expectedElements = [];
            $name = $this->name;
            $parentName = null;
            if (!$isInvalidSelf) {
                $parentName = $elementName ? $name : $this->getParentName();
            }
            if (!$description) {
                $description = "";
                if ($elementName === null || $isInvalidSelf) {
                    $expectedElements[] = $name;
                } else {
                    $children = $this->children;
                    if ($children) {
                        $expectedElements = [];
                        foreach ($this->children->getChildren() as $name => $childAndIsReq) {
                            $child = $childAndIsReq[0];
                            if ($child->count < $child->maxCount) {
                                $expectedElements[] = $child->getName();
                            }
                        }
                    } else {
                        $description = "Element '{$name}' does not have any children.";
                    }
                }
            }
            $getPosCallback->getPos($column, $line, $byteIndex);

            throw new XMLInvalidElementException(
                element: $elementName ?? $name,
                errorData: XMLInvalidElementErrorData::create()
                    ->setEColumn($column)
                    ->setELine($line)
                    ->setEByteIndex($byteIndex)
                    ->setExpectedElements($expectedElements),
                parent: $parentName,
                message: $message,
                description: $description
            );
        }

        /**
         * @param string $attribute
         * @param string[] $expectedAttributes
         * @param GetXMLParserPositionInterface $getPosCallback
         * @return void
         * @throws XMLInvalidAttributeException
         */
        protected function invalidAttribute(string $attribute,array $expectedAttributes, GetXMLParserPositionInterface $getPosCallback)
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
                    ->setExpectedAttributes($expectedAttributes)
            );
        }

        /**
         * @param string $attribute
         * @param string[] $expectedAttributes
         * @param GetXMLParserPositionInterface $getPosCallback
         * @throws XMLInvalidAttributeException
         */
        protected function duplicateAttribute(string $attribute,array $expectedAttributes, GetXMLParserPositionInterface $getPosCallback)
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
                    ->setExpectedAttributes($expectedAttributes),
                message: "Attribute '$attribute' should be specified only once"
            );
        }

        /**
         * @param string[] &$missingAttributes
         * @param GetXMLParserPositionInterface $getPosCallback
         * @return void
         * @throws XMLMissingRequiredAttributesException
         */
        protected function missingRequiredAttributes(array $missingAttributes, GetXMLParserPositionInterface $getPosCallback)
        {
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            throw new XMLMissingRequiredAttributesException(
                element: $this->name,
                errorData: XMLMissingRequiredAttributesErrorData::create()
                    ->setELine($line)
                    ->setEColumn($column)
                    ->setEByteIndex($byteIndex)
                    ->setMissingAttributes($missingAttributes)
            );
        }

        /**
         * @param (string|DefsOr)[] $missingElements
         * @param GetXMLParserPositionInterface $getPosCallback
         * @throws XMLMissingRequiredElementsException
         */
        protected function missingRequiredElements(array $missingElements, GetXMLParserPositionInterface $getPosCallback)
        {
            $getPosCallback->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            throw new XMLMissingRequiredElementsException(
                element: $this->name,
                errorData: XMLMissingRequiredElementsErrorData::create()
                    ->setELine($line)
                    ->setEColumn($column)
                    ->setEByteIndex($byteIndex)
                    ->setMissingElements($missingElements)
            );
        }
    }
}
