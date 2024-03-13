<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\TaskSourceConstants;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDisplay;
    use Illuminate\Support\Facades\DB;

    class BareTakeTask
    {

        public function __construct(
            public readonly int $id,
            public readonly int $taskInfoId,
            public readonly int $taskSourceId,
            public readonly string $name,
            public readonly string $description,
            public readonly TaskClass $minClass,
            public readonly TaskClass $maxClass,
            public readonly TaskDisplay $orientation,
            public readonly int $version
        ) {
        }

        public static function tryFetchPublic(int $taskId)
        {
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;

            $task = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable,TaskInfoConstants::COL_TASK_SOURCE_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_VERSION)
                ]
            )
                ->join(
                    $taskInfoTable,
                    DBHelper::tableCol($taskInfoTable, TaskInfoConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskTable, TaskConstants::COL_TASK_INFO_ID)
                )
                ->where(
                    DBHelper::tableCol($taskTable, TaskConstants::COL_ID),
                    '=',
                    $taskId
                )
                ->where(
                    DBHelper::tableCol($taskTable, TaskConstants::COL_IS_PUBLIC),
                    '=',
                    true
                )
                ->sharedLock()
                ->first();
            if ($task) {
                $task = new self(
                    id: DBHelper::access($task, TaskConstants::COL_ID),
                    taskInfoId: DBHelper::access($task, TaskConstants::COL_TASK_INFO_ID),
                    taskSourceId:DBHelper::access($task,TaskInfoConstants::COL_TASK_SOURCE_ID),
                    name: DBHelper::access($task, TaskInfoConstants::COL_NAME),
                    orientation: TaskDisplay::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_ORIENTATION)),
                    minClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MIN_CLASS)),
                    maxClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MAX_CLASS)),
                    description: DBHelper::access($task, TaskInfoConstants::COL_DESCRIPTION),
                    version: DBHelper::access($task, TaskConstants::COL_VERSION)
                );
            }
            return $task;
        }
    }
}
