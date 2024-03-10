<?php

namespace App\Types\XML {

    interface XMLParserEventsInterface
    {
        function elementStartHandler(BaseXMLParser $parser, string $name, array $attributes): void;

        function elementEndHandler(BaseXMLParser $parser, string $name): void;

        function elementValueHandler(BaseXMLParser $parser, string $data): void;

        function commentHandler(BaseXMLParser $parser, string $data): void;

        function unsupportedConstructHandler(BaseXMLParser $parser, mixed $data,XMLUnsupportedConstructTypeEnum $type): void;
    }
}