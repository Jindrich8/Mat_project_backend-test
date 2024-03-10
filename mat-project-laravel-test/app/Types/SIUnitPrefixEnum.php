<?php

namespace App\Types {

    enum SIUnitPrefixEnum:string
    {
        case DECI = 'd';
        case CENTI = 'c';
        case MILI = 'm';
        case MICRO = 'μ';
        case NANO = 'n';
        case PIKO = 'p';
    }
}