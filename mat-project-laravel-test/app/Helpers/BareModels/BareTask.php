<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\Models\TaskInfo;
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
                id:$access($task,TaskInfo::getPrimaryKeyName()),
                name:$access($task,TaskInfo::NAME),
                difficulty:TaskDifficulty::fromThrow($access($task,TaskInfo::DIFFICULTY)),
                display:TaskDisplay::fromThrow($access($task,TaskInfo::ORIENTATION)),
                description:$access($task,TaskInfo::DESCRIPTION),
                isPublic:$access($task,TaskInfo::IS_PUBLIC),
                version:$access($task,TaskInfo::VERSION),
                userId:$access($task,TaskInfo::USER_ID),
                createdAt:$access($task,TaskInfo::CREATED_AT),
                updatedAt:$access($task,TaskInfo::UPDATED_AT),
                minClass:TaskClass::fromThrow($access($task,TaskInfo::MIN_CLASS)),
                maxClass:TaskClass::fromThrow($access($task,TaskInfo::MAX_CLASS))
               );
        }

        public static function tryFetchById(int $id):self|null{
            return self::tryFetch(function(Builder $builder)use($id){
                $builder->where(TaskInfo::getPrimaryKeyName(),'=',$id);
            })->first(default:null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskId = TaskInfo::getPrimaryKeyName();
            $taskTable = TaskInfo::getTableName();
           $builder = DB::table($taskTable)->select(
                [
                    $taskId,
                    TaskInfo::NAME,
                    TaskInfo::MIN_CLASS,
                    TaskInfo::MAX_CLASS,
                    TaskInfo::DIFFICULTY,
                    TaskInfo::IS_PUBLIC,
                    TaskInfo::VERSION,
                    TaskInfo::USER_ID,
                    TaskInfo::CREATED_AT,
                    TaskInfo::UPDATED_AT
                ]
                );
            $modifyQuery($builder);
           $tasks = $builder->get();

           $bareTasks = $tasks->map(fn($task)=>self::fromRecord($task));
           return $bareTasks;
        }
    }
}