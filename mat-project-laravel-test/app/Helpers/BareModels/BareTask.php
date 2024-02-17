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
    use Carbon\Carbon;
    use Illuminate\Database\Query\Builder;
    use DB;

    class BareTask
    {

        public function __construct(
            public readonly int $id,
            public readonly string $name, 
            public readonly TaskDisplay $display,
            public readonly TaskDifficulty $difficulty,
            public readonly TaskClass $minClass,
            public readonly TaskClass $maxClass,
            public readonly string $description,
            public readonly Carbon $createdAt,
            public readonly Carbon|null $updatedAt,
            public readonly bool $isPublic,
            public readonly int $version,
            public readonly int $userId
            ){

        }

        /**
         * 
         */
        public static function fromRecord(array|object $task){
            $access = Utils::getAccessor($task);

            return new self(
                id:$access($task,Task::getPrimaryKeyName()),
                name:$access($task,TaskConstants::COL_NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,TaskConstants::COL_DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,TaskConstants::COL_ORIENTATION)),
                description:$access($task,TaskConstants::COL_DESCRIPTION),
                isPublic:$access($task,TaskConstants::COL_IS_PUBLIC),
                version:$access($task,TaskConstants::COL_VERSION),
                userId:$access($task,TaskConstants::COL_USER_ID),
                createdAt:$access($task,TaskConstants::COL_CREATED_AT),
                updatedAt:$access($task,TaskConstants::COL_UPDATED_AT),
                minClass:TaskClass::fromThrow($access($task,TaskConstants::COL_MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,TaskConstants::COL_MAX_CLASS))
               );
        }

        public static function tryFetchById(int $id):self|null{
            return self::tryFetch(function(Builder $builder)use($id){
                $builder->where(Task::getPrimaryKeyName(),'=',$id);
            })->first(default:null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskId = Task::getPrimaryKeyName();
            $taskTable = TaskConstants::TABLE_NAME;
           $builder = DB::table($taskTable)->select(
                [
                    $taskId,
                    TaskConstants::COL_NAME,
                    TaskConstants::COL_MIN_CLASS,
                    TaskConstants::COL_MAX_CLASS,
                    TaskConstants::COL_DIFFICULTY,
                    TaskConstants::COL_IS_PUBLIC,
                    TaskConstants::COL_VERSION,
                    TaskConstants::COL_USER_ID,
                    TaskConstants::COL_CREATED_AT,
                    TaskConstants::COL_UPDATED_AT
                ]
                );
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(fn($task)=>self::fromRecord($task));
           return $bareTasks;
        }
    }
}