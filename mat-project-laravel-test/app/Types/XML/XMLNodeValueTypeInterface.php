<?php

namespace App\Types\XML {

    enum XMLNodeValueTypeInterface
    {
        case NO_VALUE;
        case NON_EMPTY_VALUE;
        case VALUE;
    }
}