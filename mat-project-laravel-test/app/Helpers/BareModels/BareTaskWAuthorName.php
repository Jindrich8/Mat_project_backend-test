<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\Models\Task;
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
                id:$access($task,Task::getPrimaryKeyName()),
                name:$access($task,TaskConstants::COL_NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,TaskConstants::COL_DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,TaskConstants::COL_ORIENTATION)),
                description:$access($task,TaskConstants::COL_DESCRIPTION),
                authorName:$access($task,$authorNameColName),
                isPublic:$access($task,TaskConstants::COL_IS_PUBLIC),
                version:$access($task,TaskConstants::COL_VERSION),
                userId:$access($task,TaskConstants::COL_USER_ID),
                minClass:TaskClass::fromThrow($access($task,TaskConstants::COL_MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,TaskConstants::COL_MAX_CLASS))
               );
        }

        public static function tryFetchById(int $id,bool $publicOnly = true):self|null{
            return self::tryFetch(function(Builder $builder)use($id,$publicOnly){
                $builder->where(Task::getPrimaryKeyName(),'=',$id);
                if($publicOnly){
                    $builder->where(TaskConstants::COL_IS_PUBLIC,'=',true);
                }
            })->first(default:null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskId = Task::getPrimaryKeyName();
            $taskTable = TaskConstants::TABLE_NAME;
            $userTable = UserConstants::TABLE_NAME;
           $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable,$taskId),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_IS_PUBLIC),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_VERSION),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_USER_ID),
                    DBHelper::colExpression(
                        table:$userTable,
                        column:UserConstants::COL_NAME,
                        as:'authorName'
                    )
                ]
            )
            ->join(UserConstants::TABLE_NAME,User::getPrimaryKeyName(),'=',$taskTable.'.'.TaskConstants::COL_USER_ID);
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(static fn($task)=>self::fromRecord($task,'authorName'));
           return $bareTasks;
        }
    }
}