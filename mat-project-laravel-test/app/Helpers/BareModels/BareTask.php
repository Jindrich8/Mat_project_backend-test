<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\TableSpecificData\TaskDisplay;
    use App\Utils\TimeStampUtils;
    use Carbon\Carbon;
    use Illuminate\Database\Query\Builder;
    use DB;
    use Illuminate\Support\Carbon as SupportCarbon;

    class BareTask
    {

        public function __construct(
            public readonly int $id,
            public readonly int $taskInfoId,
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

       
        public static function fromRecord(array|object $task){
            $updatedAt = DBHelper::access($task,TaskConstants::COL_UPDATED_AT);
            return new self(
                id:DBHelper::access($task,TaskConstants::COL_ID),
                taskInfoId:DBHelper::access($task,TaskConstants::COL_TASK_INFO_ID),
                name:DBHelper::access($task,TaskConstants::COL_NAME),
                minClass:TaskClass::fromThrow(DBHelper::access($task,TaskInfoConstants::COL_MIN_CLASS)),
                maxClass:TaskClass::fromThrow(DBHelper::access($task,TaskInfoConstants::COL_MAX_CLASS)),
                difficulty:TaskDifficulty::fromThrow(DBHelper::access($task,TaskInfoConstants::COL_DIFFICULTY)),
                description:DBHelper::access($task,TaskInfoConstants::COL_DESCRIPTION),
                display:TaskDisplay::fromThrow(DBHelper::access($task,TaskInfoConstants::COL_ORIENTATION)),
                isPublic:DBHelper::access($task,TaskConstants::COL_IS_PUBLIC),
                version:DBHelper::access($task,TaskConstants::COL_VERSION),
                userId:DBHelper::access($task,TaskConstants::COL_USER_ID),
                createdAt:TimeStampUtils::createFromTimestampUtc(DBHelper::access($task,TaskConstants::COL_CREATED_AT)),
                updatedAt:$updatedAt ? TimeStampUtils::createFromTimestampUtc($updatedAt) : null,
               );
               
        }

        public static function tryFetchById(int $id):self|null{
            return self::tryFetch(function(Builder $builder)use($id){
                $builder->where(
                    DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_ID),
                    '=',
                    $id
                );
            })->first(default:null);
        }

         /**
         * @param callable(Builder $builder):(array|void|null) $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery){
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
           $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_IS_PUBLIC),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_VERSION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_USER_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_CREATED_AT),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_UPDATED_AT)
                ]
                )
                ->join(
                    $taskInfoTable,
                DBHelper::tableCol($taskInfoTable,TaskInfoConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskTable,TaskConstants::COL_TASK_INFO_ID)
                );

                $tasks = $modifyQuery($builder);
               
                $tasks = (is_array($tasks) ? collect($tasks) : $builder->get());

            return $tasks->map(fn($task)=>self::fromRecord($task));
        }
    }
}
