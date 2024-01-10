<?php

namespace App\Types  {

    interface GetXMLParserPosition
    {
        function getPos(?int &$column,?int &$line,?int &$byteIndex):void;
    }
}