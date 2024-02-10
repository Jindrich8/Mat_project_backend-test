<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
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
                name:$access($task,Task::NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,Task::DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,Task::ORIENTATION)),
                description:$access($task,Task::DESCRIPTION),
                isPublic:$access($task,Task::IS_PUBLIC),
                version:$access($task,Task::VERSION),
                userId:$access($task,Task::USER_ID),
                createdAt:$access($task,Task::CREATED_AT),
                updatedAt:$access($task,Task::UPDATED_AT),
                minClass:TaskClass::fromThrow($access($task,Task::MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,Task::MAX_CLASS))
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
            $taskTable = Task::getTableName();
           $builder = DB::table($taskTable)->select(
                [
                    $taskId,
                    Task::NAME,
                    Task::MIN_CLASS,
                    Task::MAX_CLASS,
                    Task::DIFFICULTY,
                    Task::IS_PUBLIC,
                    Task::VERSION,
                    Task::USER_ID,
                    Task::CREATED_AT,
                    Task::UPDATED_AT
                ]
                );
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(fn($task)=>self::fromRecord($task));
           return $bareTasks;
        }
    }
}