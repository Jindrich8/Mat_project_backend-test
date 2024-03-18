<?php

namespace App\Helpers\Database {

    use App\Exceptions\InternalException;
    use App\Helpers\EnumHelper;
    use App\Types\DBTypeEnum;
    use App\Types\SimpleQueryWheresBuilder;
    use App\Utils\DBUtils;
    use App\Utils\DebugLogger;
    use App\Utils\Utils;
    use BackedEnum;
    use DB;
    use Illuminate\Database\Query\Builder;

    class DBHelper
    {

        public static function insertFromSameByIdSingleWConstantsGetId(string $tableName,array $insertColumns,array $values,string $primaryKeyName,string $primaryKeyValue):mixed{
            $unmodifiedColumns = [];
            foreach($insertColumns as $insertColumn){
                if(!isset($values[$insertColumn])){
                    $unmodifiedColumns[]=$insertColumn;
                }
            }

            $unmodifiedValues = (array)DB::table($tableName)
            ->select($unmodifiedColumns)
            ->where($primaryKeyName, '=', $primaryKeyValue)
            ->first() ?? throw new InternalException(
                message:"Could not find row in '$tableName' with '$primaryKeyName' '$primaryKeyValue'.",
        context:[
            'tableName' => $tableName,
            'primaryKeyName'=>$primaryKeyName,
            'primaryKeyValue'=>$primaryKeyValue,
            'unmodifiedColumns'=>$unmodifiedColumns
        ]);

            // Order of array + array is important, because:
            // ,,for keys that exist in both arrays, the elements from the left-hand array will be used"
            // https://www.php.net/manual/en/language.operators.array.php
            $values += $unmodifiedValues;
            unset($unmodifiedValues);
            DebugLogger::debug(self::class."::insertFromSameByIdSingleWConstantsGetId",[
                'tableName' => $tableName,
                'values' => $values,
                'primaryKeyName' => $primaryKeyName,
            ]);
            return DB::table($tableName)
                ->insertGetId($values, $primaryKeyName);
        }

          /**
         * @param string[] $insertColumns,
         * @param array<string,bool> $selectDictIsColumn
         */
        private static function insertFromSingleWConstantsPG(string $tableName,array $insertColumns,array $selectDictIsColumn,string $selectFromTableName,SimpleQueryWheresBuilder $wheresBuilder){
            return self::insertFromWConstantsPG(
                tableName:$tableName,
                insertColumns:$insertColumns,
                selectDictIsColumn:$selectDictIsColumn,
                selectFromTableName:$selectFromTableName,
                selectAfterFromExpr:" WHERE ".$wheresBuilder->getWheresStr()
            );
        }

        /**
         * @param string[] $insertColumns,
         * @param array<string,bool> $selectDictIsColumn
         */
        private static function insertFromWConstantsPG(string $tableName,array $insertColumns,array $selectDictIsColumn,string $selectFromTableName,?string $selectAfterFromExpr = null,array $selectAfterFromExprBindings = []){
            $grammar = DB::getQueryGrammar();

            $tableName = $grammar->wrapTable($tableName);
            $selectFromTableName = $grammar->wrapTable($selectFromTableName);

           $insertColumnsStr = $grammar->columnize($insertColumns);

           $selectColumnsStr = "";
           $bindings = [];
           $isColumn = $selectDictIsColumn[array_key_first($selectDictIsColumn)];
           $exprs = [];
           foreach($selectDictIsColumn as $selectExpr => $selectExprIsColumn){
            if($isColumn !== $selectExprIsColumn){
                if($exprs){
                    if($selectColumnsStr){
                        $selectColumnsStr.=',';
                    }
                    if($isColumn){
                        $selectColumnsStr.=$grammar->columnize($exprs);
                    }
                    else{
                        $selectColumnsStr.=$grammar->parameterize($exprs);
                        $bindings[]=$exprs;
                    }
                }
                $isColumn = $selectExprIsColumn;
                $exprs =[];
            }
            $exprs[]=$selectExpr;

           }

           
            $query = "INSERT INTO $tableName ($insertColumnsStr) ("
            ."SELECT $selectColumnsStr FROM $selectFromTableName "
            .($selectAfterFromExpr ?: '')
            .");";

            array_push($bindings,$selectAfterFromExprBindings);
            
            return DB::insert($query,$bindings);
        }

        /**
         * @param string[] $columns
         * @param array<array<mixed>> $values
         * @param callable():int[] $getIdsIfNotSupported
         * @return int[]|null
         */
        public static function insertAndGetIds(string $tableName, string $primaryKeyName, array $columns, array &$values,callable $getIdsIfNotSupported,bool $unsetValuesArray = false): array
        {
            if(DBUtils::getDBType() === DBTypeEnum::POSTGRESQL){
                return PgDB::insertAndGetIds($tableName,$primaryKeyName,$columns,$values,$unsetValuesArray);
            }
            else{
                $valuesCount = count($values);
                $columnsCount = count($columns);
                $transformed = [];
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
                    $transformed[] = array_combine($columns,$value);
                    if($unsetValuesArray){
                        unset($values[$i]);
                    }
                }
               $inserted = DB::table($tableName)
                ->insert($transformed);
                if(!$inserted){
                    return null;
                }
                $ids = $getIdsIfNotSupported();
                if(count($ids)!== $valuesCount){
                    return null;
                }
                return $ids;
            }
        }

        /**
         * @template T of \BackedEnum
         * @param class-string<T> $enum
         * @return T
         */
        public static function accessAsEnum(mixed $record, string $prop,string $enum):BackedEnum{
            return EnumHelper::fromThrow($enum,self::access($record,$prop));
        }

        /**
         * @template T
         * @param T $default
         * @return mixed|T
         */
        public static function tryToAccess(mixed $record, string $prop, mixed $default = null)
        {
            return $record->{$prop} ?? $default;
        }

        public static function access(mixed $record, string $prop)
        {
            return $record->{$prop};
        }

        public static function addOrReplaceSelectColumnsWAliases(Builder $builder, array &$columnsToAliases, bool $add = false): Builder
        {
            $columns = [];
            while ($columnsToAliases) {
                $column = Utils::arrayFirstKey($columnsToAliases);
                $alias = array_shift($columnsToAliases);
                if (is_integer($column)) {
                    $columns[] = $alias;
                } else {
                    $columns[] = "$column AS $alias";
                }
            }
            if ($add) {
                $builder->addSelect($columns);
            } else {
                $builder->select($columns);
            }
            return $builder;
        }


        public static function selectColumnsWAliases(Builder $builder, array &$columnsToAliases): Builder
        {
            return self::addOrReplaceSelectColumnsWAliases($builder, $columnsToAliases, add: false);
        }

        public static function addSelectColumnsWAliases(Builder $builder, array &$columnsToAliases): Builder
        {
            return self::addOrReplaceSelectColumnsWAliases($builder, $columnsToAliases, add: true);
        }

        public static function tableCol(string $table, string $col): string
        {
            return $table . '.' . $col;
        }

        public static function colFromTableAsCol(string $table, string $col): string
        {
            return self::tableCol($table, $col) . ' AS ' . $col;
        }

        public static function colExpression(string $column, string $table = '', string $as = ''): string
        {
            $expr = $column;
            if ($table) {
                $expr = self::tableCol($table, $column);
            }
            $expr .= ($as ? ' AS ' . $as : '');
            return $expr;
        }


    }
}
