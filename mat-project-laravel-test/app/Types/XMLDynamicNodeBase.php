<?php
namespace App\Types {
    use App\Types\XMLNodeBase;

    abstract class XMLDynamicNodeBase extends XMLNodeBase
    {
        public abstract function change(XMLNodeBase $newParent,string $newName):void;
    }
}