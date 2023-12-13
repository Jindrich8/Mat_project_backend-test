<?php

namespace App\Types {

    use Illuminate\Support\Str;

    class XPathBuilder
    {
        private string $xpath;

        public function __construct(){
            $this->xpath = "";
        }

        public function getXPathStr():string{
            return $this->xpath;
        }

        public function append(string $descendant,int $elementIndex = 0){
               $descendant = rtrim($descendant,'/');
            $this->xpath.="/*[".($elementIndex+1)."]/self::".$descendant;
        }
    }
}