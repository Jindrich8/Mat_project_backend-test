<?php

namespace App\Types {

    use App\Types\XMLAttributes;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBase;

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
         * @param string $name
         * @param ?XMLAttributes $attributes
         * @param ?T $parent
         * @param bool $shouldHaveAtLeastOneChild
         * @param int $maxCount
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