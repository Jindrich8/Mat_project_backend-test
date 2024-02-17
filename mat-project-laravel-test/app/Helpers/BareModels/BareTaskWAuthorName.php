<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
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
                name:$access($task,Task::NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,Task::DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,Task::ORIENTATION)),
                description:$access($task,Task::DESCRIPTION),
                authorName:$access($task,$authorNameColName),
                isPublic:$access($task,Task::IS_PUBLIC),
                version:$access($task,Task::VERSION),
                userId:$access($task,Task::USER_ID),
                minClass:TaskClass::fromThrow($access($task,Task::MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,Task::MAX_CLASS))
               );
        }

        public static function tryFetchById(int $id,bool $publicOnly = true):self|null{
            return self::tryFetch(function(Builder $builder)use($id,$publicOnly){
                $builder->where(Task::getPrimaryKeyName(),'=',$id);
                if($publicOnly){
                    $builder->where(Task::IS_PUBLIC,'=',true);
                }
            })->first(default:null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskId = Task::getPrimaryKeyName();
            $taskTable = Task::getTableName();
            $userTable = User::getTableName();
           $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable,$taskId),
                    DBHelper::colFromTableAsCol($taskTable,Task::NAME),
                    DBHelper::colFromTableAsCol($taskTable,Task::MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,Task::MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskTable,Task::DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskTable,Task::IS_PUBLIC),
                    DBHelper::colFromTableAsCol($taskTable,Task::VERSION),
                    DBHelper::colFromTableAsCol($taskTable,Task::USER_ID),
                    DBHelper::colExpression(
                        table:$userTable,
                        column:User::NAME,
                        as:'authorName'
                    )
                ]
            )
            ->join(User::getTableName(),User::getPrimaryKeyName(),'=',$taskTable.'.'.Task::USER_ID);
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(static fn($task)=>self::fromRecord($task,'authorName'));
           return $bareTasks;
        }
    }
}