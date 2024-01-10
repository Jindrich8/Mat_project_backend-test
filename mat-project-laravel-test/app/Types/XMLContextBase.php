<?php

namespace App\Types {

    use App\Helpers\CreateTask\TaskRes;

    abstract class XMLContextBase implements GetXMLParserPosition
    {
        public abstract function getTaskRes():TaskRes;
    }
}