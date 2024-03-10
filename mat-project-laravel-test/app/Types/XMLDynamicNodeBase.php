<?php
namespace App\Types {

    abstract class XMLDynamicNodeBase extends XMLNodeBase
    {
        public abstract function change(XMLNodeBase $newParent,string $newName):void;
    }
}
