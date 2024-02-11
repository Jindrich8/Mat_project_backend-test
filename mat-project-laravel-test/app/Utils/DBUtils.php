<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;
    use BackedEnum;

    class DBUtils
    {

        public static function dropType(string $type){
            if(!DB::statement("DROP TYPE ?",[$type])){
                throw new InternalException(
                    message:"Could not drop type '$type'",
            context:['type' =>$type]
        );
            }
        }

        /**
         * @param string $table
         * @param string $column
         * @param class-string<BackedEnum> $enum
         */
        public static function addIntEnumConstraint(string $table,string $column,string $enum){
            self::addMinMaxConstraint(
                table:$table,
            column:$column,
            minIncl:0,
            max:count($enum::cases())
            );
        }

        public static function addCheckConstraint(string $table, string $condition)
        {
            if (!DB::statement("ALTER TABLE $table ADD CHECK($condition);")) {
                throw new InternalException(
                    message: "Could not create CHECK constraint for table '$table' with condition '$condition'.",
                    context: [
                        'table' => $table,
                        'condition' => $condition
                    ]
                );
            }
        }

        public static function addMinMaxConstraint(string $table,string $column,int $max,int $minIncl = 0){
            if($max <= $minIncl){
                throw new InternalException(
                    message:"Max '$max' needs to be bigger than minIncl '$minIncl'",
                    context:[
                   'table' => $table,
                   'column' => $column,
                   'minIncl' => $minIncl,
                   'max' => $max
                ]);
            }
            $query = "ALTER TABLE $table ADD CHECK (? >= ? AND ? < ?);";
            if(!DB::statement(
            query:$query,
            bindings:[$column,$minIncl,$column,$max]
        )){
            throw new InternalException(
            message:"Could not create CHECK constraint for table '$table' on column '$column'",
            context:[
           'table' => $table,
           'column' => $column,
           'minIncl' => $minIncl,
           'max' => $max,
           'query' => $query
        ]);
        }
        }
        /**
         * @param class-string<BackedEnum> $enumName
         */
        public static function getPGEnumTypeName(string $enumName):string{
            return Str::snake($enumName)."_pq_enum";
        }

        /**
         * @param string $name
         * @param string[] $values
         */
        public static function tryCreatePgEnum(string $name,array &$values):bool{
            if(!$values)return true;
            $valuesCount = count($values);

           $success = DB::statement(
                query:"CREATE TYPE $name AS ENUM (".Utils::wrapAndImplode("'",',',$values).');'
            );
            return $success;
        }

         /**
         * @param string $name
         * @param string[] $values
         */
        public static function createPgEnum(string $name,array $values):void{
            if(!self::tryCreatePgEnum($name,$values)){
                throw new InternalException(
                    message:"Could not create PostgreSQL enum '$name' with values '$values'.",
            context:[
                'enumName' => $name,
                'enumValues' => $values
            ]);
            }
        }
    }
}