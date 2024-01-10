<?php

namespace App\Types {

    enum XMLUnsupportedConstructType
    {
        case NOTATION_DECLARATION;
        case START_NAMESPACE_DECLARATION;
        case EXTERNAL_ENTITY_REFERENCE;
        case UNPARSED_ENTITY_DECLARATION;
        case PROCESSING_INSTRUCTION;
        case UNKNOWN_CONSTRUCT;
    }
}