<?php

namespace App\Helpers\Database {

    use App\Exceptions\InternalException;
    use App\Utils\DebugLogger;
    use App\Utils\Utils;
    use DB;

    class PgDB extends DB
    {
        /**
         * @param string[] $columns
         * @param array<array<mixed>> $values
         * @return int[]|null
         */
        public static function insertAndGetIds(string $tableName, string $primaryKeyName, array $columns, array &$values,bool $unsetValuesArray = false): array
        {
            $bindings = [];
            $valuesCount = count($values);
            $columnsCount = count($columns);
            $rowBindingTemplate = '(?'.str_repeat(',?',$columnsCount-1).')';

            $query ="INSERT INTO $tableName (" . Utils::wrapAndImplode('"',",",$columns) . ") VALUES $rowBindingTemplate"
            . str_repeat(",$rowBindingTemplate", count($values) - 1)
                        . "RETURNING $primaryKeyName";
            unset($columns);

            for($i = 0;$i < $valuesCount;++$i){
                $value = &$values[$i];
                if(count($value) !== $columnsCount){
                    throw new InternalException("The values array element arrays should have same length as columns array.",
                    context:[
                        'tableName' => $tableName,
                    'primaryKeyName' => $primaryKeyName,
                    'values' => $values
                ]);
                }
                array_push($bindings,...$value);
                if($unsetValuesArray){
                    unset($values[$i]);
                }
            }
            DebugLogger::log("bindings",$bindings);
            $ids = DB::select(
                $query,
                bindings: $bindings,
                useReadPdo: false
            );
            if (!array_is_list($ids)) {
                $ids = $ids[array_key_first($ids)];
            }
            if(count($ids)!== $valuesCount){
               return null;
            }
            for($i = 0; $i < $valuesCount; ++$i){
                $id = $ids[$i];
                if(!is_int($id)){
                    $ids[$i] = is_object($id) ?
                    $id->{$primaryKeyName}
                    : $id[$primaryKeyName];
                }
            }
            return $ids;
        }
    }
}
