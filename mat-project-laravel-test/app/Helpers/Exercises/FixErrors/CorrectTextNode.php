<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Exceptions\XMLInvalidElementValueException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBaseWParentNode;

    /**
     * @extends XMLNodeBaseWParentNode<FixErrorsXMLCreateNode>
     */
    class CorrectTextNode extends XMLNodeBaseWParentNode
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
                name:$config->correctText->name,
                parent:$parent,
                maxCount:1
            );
        }

        public function appendValue(string $value, XMLContextBase $context): void
        {
           $content = $this->parent->getContent();
           $content->correctText = ($content->correctText ?? "").$value;
        }

        /**
         * @throws XMLInvalidElementValueException
         */
        protected function validate(XMLContextBase $context): void
        {
            $config = TaskSrcConfig::get()->getFixErrorsConfig();
            $content = $this->parent->getContent();
            $error = $config->correctText->validateWLength($content->correctText,$length);
            if($error !== null){
                $this->invalidValue($error);
            }
        }
    }
}
