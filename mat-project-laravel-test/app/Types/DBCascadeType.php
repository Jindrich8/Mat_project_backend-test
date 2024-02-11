<?php

namespace App\Types {

    enum DBCascadeType
    {
        case DELETE;
        case UPDATE;
    }
}