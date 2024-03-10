<?php

namespace App\Types {

    use IntlPartsIterator;

    enum CharIteratorKeyType:int
    {
        case CHAR_START = IntlPartsIterator::KEY_LEFT;
        case CHAR_END = IntlPartsIterator::KEY_RIGHT;
        case CHAR_INDEX = IntlPartsIterator::KEY_SEQUENTIAL;
    }
}
