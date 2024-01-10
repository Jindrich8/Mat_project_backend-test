<?php

namespace App\Types {

    interface BaseXMLParser extends GetXMLParserPosition
    {
        function parse(string $data,bool $isFinal = false):?XMLParserError;

        function setEvents(XMLParserEvents $events):void;

        function free():void;

        static function create(XMLParserEvents $events):static;
    }
}