<?php

namespace App\Helpers\Database {

    use App\Exceptions\InternalException;
    use App\Utils\Utils;
    use DB;
    use Illuminate\Database\Query\Builder;

    class DBHelper
    {
        public static function addOrReplaceSelectColumnsWAliases(Builder $builder,array &$columnsToAliases,bool $add = false):Builder{
            $columns = [];
            while($columnsToAliases){
                $column = Utils::arrayFirstKey($columnsToAliases);
                $alias = array_shift($columnsToAliases);
                if(is_integer($column)){
                    $columns[]=$alias;
                }
                else{
                    $columns[]="$column AS $alias";
                }
            }
            if($add){
                $builder->addSelect($columns);
            }
            else{
                $builder->select($columns);
            }
            return $builder;
        }


        public static function selectColumnsWAliases(Builder $builder,array &$columnsToAliases):Builder{
           return self::addOrReplaceSelectColumnsWAliases($builder,$columnsToAliases,add:false);
        }

        public static function addSelectColumnsWAliases(Builder $builder,array &$columnsToAliases):Builder{
            return self::addOrReplaceSelectColumnsWAliases($builder,$columnsToAliases,add:true);
         }

         public static function colFromTableAsCol(string $table,string $col):string{
            return $table . $col . ' AS ' . $col;
        }

        public static function colExpression(string $column,string $table = '',string $as = ''):string{
            return ($table ? $table . '.' : '') . $column . ($as ?' AS ' . $as : '');
        }


        /**
         * @param array<string,mixed> $pk
         * @param bool $try
         * @param string $table
         **/
        public static function deleteWCompositeKey(array $pk,string $table,bool $try = false):bool{
        $pkCount = count($pk);
           $whereClauseAddition = $pkCount > 1 ? str_repeat(" AND ?=?",$pkCount-1) : '';
           $query = "DELETE FROM ? WHERE ? = ?".$whereClauseAddition;
           $bindings = [$table];
           while(($name = Utils::arrayFirstKey($pk)) && ($value = Utils::arrayShift($pk))){
            $bindings[]=$name;
            $bindings[]=$value;
           }
           /**
            * @var bool $deleted
            */
         $deleted =  DB::transaction(function()use($query,$bindings,$pk,$table,$try){
            $deletedCount = DB::delete($query,$bindings);
            if($deletedCount !== 1){
                if($deletedCount === 0){
                    if($try){
                    throw new InternalException("Specified row from '$table' table could not be deleted, because it does not exist!",
                    context:[
                        'deleteQuery'=>$query,
                        'bindings'=>$bindings,
                        'pk'=>$pk,
                        'table'=>$table,
                        'deletedCount'=>$deletedCount
                    ]);
                }
                }
                else{
                    throw new InternalException("Row was not deleted, because more than one row was found by given set of identifiers!",
                    context:[
                        'deleteQuery'=>$query,
                        'bindings'=>$bindings,
                        'pk'=>$pk,
                        'table'=>$table,
                        'deletedCount'=>$deletedCount
                    ]);
                }
            }
            return $deletedCount !== 0;
        });
        return $deleted;
        }
        
    }
}