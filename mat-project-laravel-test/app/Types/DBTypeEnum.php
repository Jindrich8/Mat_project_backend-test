<?php

namespace App\Types {

    enum DBTypeEnum:string
    {
        use BackedEnumTrait;
        case POSTGRESQL = 'pgsql';
        case MYSQL = 'mysql';
        case SQLITE = 'sqlite';
    }
}