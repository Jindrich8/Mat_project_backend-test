<?php

namespace App\Helpers\Database {

    use App\Exceptions\InternalException;
    use Exception;

    class DBJsonHelper
    {
            public static function decode(string $json,string $table,string $column,mixed $id):mixed{
                try{
               return json_decode($json,flags:JSON_THROW_ON_ERROR);
                }
                catch(Exception $e){
                    throw new InternalException("Failed to decode '$table' table '$column' column for '$id' row.",
                    previous:$e,
                    context:[
                        'table' => $table,
                        'column' => $column,
                        'id' => $id,
                        'json' => $json
                    ]);
                }
               
            }
    }
}