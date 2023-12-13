<?php

namespace App\Helpers\CreateTask{

    class XMLOneUseNode extends XMLNodeBase{

        /**
         * @param string $name
         * @param array{string,callable(string $value,TaskRes $taskRes):void} $requiredAttrs
         * @param array{string,callable(string $value,TaskRes $taskRes):void} $nonRequiredAttrs
         * @param XMLNodeBase|null $parent
         * @param XMLNodeBase[] $children
         * @param ?callable(XMLOneUseNode $thisNode, iterable $attributes, TaskRes $taskRes, ?string $name):void $validateStart
         * @param ?callable(XMLOneUseNode $thisNode, TaskRes $taskRes):void $validateFunc
         * @param ?callable(XMLOneUseNode $thisNode, string $value,TaskRes $taskRes):void $appendValue
         */
        public function __construct(
            string $name,
            private readonly array $requiredAttrs = [],
        private readonly array $nonRequiredAttrs = [],
        ?XMLNodeBase $parent = null,
        array $children = [],
        private readonly ?callable $validateStart = null,
        private readonly ?callable $validateFunc = null,
        private readonly ?callable $appendValue = null,
        
        )
        {
            parent::__construct($name,$children,$parent);
        }

        function getNonRequiredAttributes(): array
        {
            return $this->nonRequiredAttrs;
        }

        function getRequiredAttributes(): array
        {
            return $this->requiredAttrs;
        }

        function appendValue(string $value, TaskRes $taskRes,callable $getParserPosition): void
        {
            if($appendValue = $this->appendValue){
                $appendValue($this,$value,$taskRes);
            }
            else{
                $this->valueNotSupported();
            }
        }

        function validateStart(iterable $attributes, TaskRes $taskRes, ?string $name = null): void
        {
            if($validateStart = $this->validateStart){
                $validateStart($this,$attributes,$taskRes,$name);
            }
            parent::validateStart($attributes,$taskRes,$name);
        }

        function validate(TaskRes $taskRes): void
        {
            if($validate = $this->validateFunc){
            $validate($this,$taskRes);
            }
        }

    }
}