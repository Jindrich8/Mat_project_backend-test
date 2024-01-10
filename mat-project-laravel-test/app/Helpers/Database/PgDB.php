<?php

namespace App\Helper\Database {

    use App\Exceptions\InternalException;
    use DB;

    class PgDB extends DB
    {
        /**
         * @param string $tableName
         * @param string $primaryKeyName
         * @param string[] $columns
         * @param array<array<mixed>> $values
         * @return array
         */
        public static function insertAndGetIds(string $tableName, string $primaryKeyName, array $columns, array &$values,bool $unsetValuesArray = false): array
        {
            $bindings = [];
            $valuesCount = count($values);
            $columnsCount = count($columns);
            $query ="INSERT INTO $tableName (" . implode(",", $columns) . ") VALUES (?,?,?)"
            . str_repeat(",(?,?,?)", count($values) - 1)
                        . "RETURNING $primaryKeyName";
            unset($columns);

            for($i = 0;$i < $valuesCount;++$i){
                $value = &$values[$i];
                if(count($value) !== $columnsCount){
                    throw new InternalException("The values array element arrays should have same length as columns array.",
                    context:[
                        'tableName' => $tableName,
                    'primaryKeyName' => $primaryKeyName,
                    'columns' => $columns, 
                    'values' => $values
                ]);
                }
                $bindings[]=$value;
                if($unsetValuesArray){
                    unset($values[$i]);
                }
            }
            $ids = DB::select(
                DB::raw($query),
                bindings: $bindings,
                useReadPdo: false
            );
            if (!array_is_list($ids)) {
                $ids = $ids[array_key_first($ids)];
            }
            if(count($ids)!== $valuesCount){
                throw new InternalException(
                    message:"Number of returned ids should be equal to number of inserted rows.",
                context:[
                    'query' => $query,
                    'tableName' => $tableName,
                'primaryKeyName' => $primaryKeyName,
                'columns' => $columns, 
                'values' => $values,
                'unsetValuesArray'=>$unsetValuesArray
            ]);
            }
            return $ids;
        }
    }
}