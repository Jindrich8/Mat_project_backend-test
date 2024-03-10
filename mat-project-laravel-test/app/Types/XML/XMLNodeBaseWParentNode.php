<?php

namespace App\Types\XML {

    /**
     * @template T of XMLNodeBase
     */
    abstract class XMLNodeBaseWParentNode extends XMLNodeBase
    {
        /**
         * @var ?T $parent
         */
        protected ?XMLNodeBase $parent;

        /**
         * @return ?T
         */
        protected function moveUp(XMLContextBase $context):?XMLNodeBase{
            return $this->parent;
        }

        protected function getParentName():?string{
            return $this->parent?->getName();
        }


        public function getParentObjectId(): object
        {
            return $this->parent;
        }
         /**
          * @param ?T $parent
          */
        protected function __construct(string $name,?XMLAttributes $attributes = null,?XMLNodeBase $parent = null,bool $shouldHaveAtLeastOneChild = false,int $maxCount = PHP_INT_MAX,bool $isValueNode = true)
        {
            $this->parent = $parent;
            parent::__construct(
                name:$name,
                attributes:$attributes,
                shouldHaveAtLeastOneChild:$shouldHaveAtLeastOneChild,
                maxCount:$maxCount,
                isValueNode:$isValueNode
            );
        }
    }
}
