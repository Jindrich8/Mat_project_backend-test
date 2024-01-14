<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNodeValueType;

    /**
     * @extends XMLNodeBaseWParentNode<FixErrorsXMLCreateNode>
     */
    class WrongTextNode extends XMLNodeBaseWParentNode
    {

        public static function create(FixErrorsXMLCreateNode $parent):self{
            return new self($parent);
        }

        /**
         * @param FixErrorsXMLCreateNode $parent
         */
        protected function __construct(FixErrorsXMLCreateNode $parent){
           $config = TaskSrcConfig::get()->getFixErrorsConfig();
            parent::__construct(
                name:$config->wrongTextName,
                parent:$parent,
                maxCount:1
            );
        }

        public function appendValue(string $value, XMLContextBase $context): void
        {
           $content = $this->parent->getContent();
           $content->wrongText=($content->wrongText ?? "").$value;
        }
    }
}