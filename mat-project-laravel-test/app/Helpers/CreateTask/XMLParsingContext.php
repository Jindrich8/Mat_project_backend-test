<?php

namespace App\Helpers\CreateTask{

    use App\Types\Coords;

    interface XMLParsingContext{

        function getValuePartStartCoords():?Coords;
        function getElementStartCoords():?Coords;
        function getElementEndCoords():?Coords;
    }
}