<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\Models\TaskInfo;
    use App\Models\User;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\TableSpecificData\TaskDisplay;
    use App\Utils\Utils;
    use Illuminate\Database\Query\Builder;
    use DB;

    class BareTaskWAuthorName
    {

        public function __construct(
            public readonly int $id,
            public readonly string $name, 
            public readonly TaskDisplay $display,
            public readonly TaskDifficulty $difficulty,
            public readonly TaskClass $minClass,
            public readonly TaskClass $maxClass,
            public readonly string $description,
            public readonly string $authorName,
            public readonly bool $isPublic,
            public readonly int $version,
            public readonly int $userId
            ){

        }

        /**
         * 
         */
        public static function fromRecord(array|object $task,string $authorNameColName){
            $access = Utils::getAccessor($task);
            return new self(
                id:$access($task,TaskInfo::getPrimaryKeyName()),
                name:$access($task,TaskInfo::NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,TaskInfo::DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,TaskInfo::ORIENTATION)),
                description:$access($task,TaskInfo::DESCRIPTION),
                authorName:$access($task,$authorNameColName),
                isPublic:$access($task,TaskInfo::IS_PUBLIC),
                version:$access($task,TaskInfo::VERSION),
                userId:$access($task,TaskInfo::USER_ID),
                minClass:TaskClass::fromThrow($access($task,TaskInfo::MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,TaskInfo::MAX_CLASS))
               );
        }

        public static function tryFetchById(int $id,bool $publicOnly = true):self|null{
            return self::tryFetch(function(Builder $builder)use($id,$publicOnly){
                $builder->where(TaskInfo::getPrimaryKeyName(),'=',$id);
                if($publicOnly){
                    $builder->where(TaskInfo::IS_PUBLIC,'=',true);
                }
            })->first(default:null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskId = TaskInfo::getPrimaryKeyName();
            $taskTable = TaskInfo::getTableName();
            $userTable = User::getTableName();
           $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable,$taskId),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::NAME),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::IS_PUBLIC),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::VERSION),
                    DBHelper::colFromTableAsCol($taskTable,TaskInfo::USER_ID),
                    DBHelper::colExpression(
                        table:$userTable,
                        column:User::NAME,
                        as:'authorName'
                    )
                ]
            )
            ->join(User::getTableName(),User::getPrimaryKeyName(),'=',$taskTable.'.'.TaskInfo::USER_ID);
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(static fn($task)=>self::fromRecord($task,'authorName'));
           return $bareTasks;
        }
    }
}