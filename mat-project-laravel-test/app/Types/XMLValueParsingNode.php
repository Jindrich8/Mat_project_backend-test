<?php

namespace App\Types {

    use App\Exceptions\InternalException;

    abstract class XMLValueParsingNode extends XMLNodeBaseWParentNode
    {
        private ?XMLNodeBase $contentNode;

        public abstract function getContentNode(XMLContextBase $context):XMLNodeBase;

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes, $context, $name);
           $node = $this->getContentNode($context);

           $this->contentNode = $node;
           $this->contentNode->validateStart($attributes,$context,$name);

        }

        private function checkContentNode(XMLContextBase|GetXMLParserPosition $context):XMLNodeBase{
           $node = $this->contentNode;
           if($node === null){
            throw new InternalException("Content node should not be null.",
            ['xmlValueParsingNode'=>$this,'context'=>$context]);
           }
           return $node;
        }

        public function appendValue(string $value, XMLContextBase $context): void
        {
            $node = $this->checkContentNode($context);
            $node->appendValue($value,$context);
        }

        public function getChild(string $name, GetXMLParserPosition $getParserPosition): XMLNodeBase
        {
            $node = $this->checkContentNode($getParserPosition);
            return $node->getChild($name,$getParserPosition);
        }

        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $node = $this->checkContentNode($context);
            $node->validate($context);
        }
    }
}
