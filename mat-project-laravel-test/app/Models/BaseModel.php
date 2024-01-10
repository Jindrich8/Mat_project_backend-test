<?php

namespace App\Models {

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Str;

    abstract class BaseModel extends Model
    {
        /**
         * @var array{0:string,1:string} $dict
         * table, primary key
         */
        private static array $dict = [];


        public static function getTableName():string{
            return self::getInfo()[0];
        }

        public static function getPrimaryKeyName():string{
            return self::getInfo()[1];
        }

        
        private static function getInfo(){
            return (self::$dict[static::class]??= self::getModelInfo(new static));
        }

   
        private static function getModelInfo(BaseModel $model){
            return [$model->getTable(),$model->getKeyName()];
        }

        public static function storeModelInfo(BaseModel $model):BaseModel{
            self::$dict[$model::class]??=self::getModelInfo($model);
            return $model;
        }
    }
}