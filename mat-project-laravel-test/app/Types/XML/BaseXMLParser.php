<?php

namespace App\Types\XML {

    interface BaseXMLParser extends GetXMLParserPositionInterface
    {
        function parse(string $data,bool $isFinal = false):?XMLParserError;

        function setEvents(XMLParserEventsInterface $events):void;

        function free():void;

        static function create(XMLParserEventsInterface $events):static;
    }
}