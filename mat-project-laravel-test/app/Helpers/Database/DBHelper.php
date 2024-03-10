<?php

namespace App\Helpers\Database {

    use App\Helpers\EnumHelper;
    use App\Utils\Utils;
    use BackedEnum;
    use Illuminate\Database\Query\Builder;

    class DBHelper
    {

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
