<?php

namespace App\Types\XML {

    use App\Helpers\CreateTask\TaskRes;

    abstract class XMLContextBase implements GetXMLParserPositionInterface
    {
        public abstract function getTaskRes():TaskRes;
    }
}