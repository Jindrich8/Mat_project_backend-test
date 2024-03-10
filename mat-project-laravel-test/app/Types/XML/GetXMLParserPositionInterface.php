<?php

namespace App\Types\XML  {

    interface GetXMLParserPositionInterface
    {
        function getPos(?int &$column,?int &$line,?int &$byteIndex):void;
    }
}