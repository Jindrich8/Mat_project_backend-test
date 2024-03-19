<?php

namespace App\Utils {

    use App\Exceptions\InternalException;
    use App\Helpers\Database\PgDB;
    use App\Types\DBTypeEnum;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;
    use BackedEnum;
    use Throwable;

    class DBUtils
    {
        public static function getDBType(): ?DBTypeEnum
        {
            $type = DBTypeEnum::tryFrom(DB::connection()->getDriverName() ?? env('DB_CONNECTION'));
            return $type;
        }

        // public static function changeColumnDataType(string $table,string $column,string $type){
        //     $db = self::getDBType();
        //     $queries = match ($db) {
        //         DBTypeEnum::POSTGRESQL => ["ALTER TABLE $table DROP $column","ALTER TABLE $table ADD $column",
        //         DBTypeEnum::MYSQL => "ALTER TABLE $table MODIFY $column float",
        //         default => throw new InternalException(
        //             message: "Unsupported database '$db' for changeing column data type.",
        //             context: [
        //                 'database' => $db->value,
        //                 'table' => $table,
        //                 'column' => $column,
        //                 'type' => $type
        //             ]
        //         )
        //     };
        //     DB::transaction(function()use(&$queries){
        //         foreach($queries as $query){
        //     if(!DB::statement($query)){
        //         throw new InternalException("Could not change column '$column' in table '$table' to '$type'",
        //         context:[
        //             'table' => $table,
        //             'column' => $column,
        //             'type' => $type
        //         ]);
        //     }
        // }
        // });
        // }

        public static function dropType(string $type)
        {
            if (!DB::statement("DROP TYPE ?", [$type])) {
                throw new InternalException(
                    message: "Could not drop type '$type'",
                    context: ['type' => $type]
                );
            }
        }

        /**
         * @param string $table
         * @param string $column
         * @param class-string<BackedEnum> $enum
         */
        public static function addIntEnumConstraint(string $table, string $column, string $enum)
        {
            self::addMinMaxConstraint(
                table: $table,
                column: $column,
                max: count($enum::cases()),
                min: 0
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

        public static function addPercentDecimalConstraint(string $table, string $column)
        {
            self::addMinMaxConstraint(
                table: $table,
                column: $column,
                max: 1,
                min: 0,
                maxIsInclusive: true,
                minIsInclusive: true
            );
        }


        public static function addMinMaxConstraint(string $table, string $column, int $max, int $min = 0, bool $maxIsInclusive = false, bool $minIsInclusive = true)
        {
            $minOperator = '>';
            if ($minIsInclusive) {
                $minOperator .= '=';
            }
            $maxOperator = '<';
            if ($maxIsInclusive) {
                $maxOperator .= '=';
            }
            if ($max < $min || (!$minIsInclusive || !$maxIsInclusive) && $min === $max) {
                $maxMustBe = "greater than";
                if ($minIsInclusive && $maxIsInclusive) {
                    $maxMustBe .= " or equal to";
                }

                throw new InternalException(
                    message: "Max '$max' must be $maxMustBe min '$min'",
                    context: [
                        'table' => $table,
                        'column' => $column,
                        'min' => $min,
                        'max' => $max,
                        'minIsInclusive' => $minIsInclusive,
                        'maxIsInclusive' => $maxIsInclusive
                    ]
                );
            }
            $query = "ALTER TABLE $table ADD CHECK ($column $minOperator $min AND $column $maxOperator $max);";
            if (!DB::statement(
                query: $query
            )) {
                throw new InternalException(
                    message: "Could not create CHECK constraint for table '$table' on column '$column'",
                    context: [
                        'table' => $table,
                        'column' => $column,
                        'min' => $min,
                        'max' => $max,
                        'minIsInclusive' => $minIsInclusive,
                        'maxIsInclusive' => $maxIsInclusive,
                        'query' => $query
                    ]
                );
            }
        }

        public static function ensureAutoUpdateUpdatedAtTimestamp(string $tableName):void{
            try{
            DB::table($tableName)
            ->select(['updated_at'])
            ->limit(1)
            ->first();
            }
            catch(Throwable $e){
                throw new InternalException(
                    message:"There is no 'updated_at' column on table '$tableName'.",
                context:[
                    'exception' => $e,
                    'tableName' => $tableName
                ]);
            }

           $db = self::getDBType();
           if($db === DBTypeEnum::POSTGRESQL){
            PgDB::autoUpdateUpdatedAtTimestampTrigger($tableName);
           }
           else if($db !== DBTypeEnum::MYSQL){
            throw new InternalException(
                message:"Auto updated_at timestamp no supported for this database",
                context:[
                    'db' => $db
                ]
            );
           }
        }

    }
}
