<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\UserConstants;
    use App\TableSpecificData\TaskDisplay;
    use Illuminate\Support\Facades\DB;

    class BareEvaluateTask
    {

        public function __construct(
            public readonly int $id,
            public readonly string $name,
            public readonly string $description,
            public readonly int $taskInfoId,
            public readonly TaskDisplay $orientation,
            public readonly int $version,
            public readonly int $authorId,
            public readonly string $authorName
        ) {
        }

        public static function tryFetchPublic(int $taskId)
        {
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;

            $userTable = UserConstants::TABLE_NAME;
            $authorNameCol = 'authorName';
            $task = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_VERSION),
                    DBHelper::colFromTableAsCol($taskTable,TaskConstants::COL_USER_ID),
                    DBHelper::colExpression(
                        table:$userTable,
                        column:UserConstants::COL_NAME,
                        as:$authorNameCol
                    )
                ]
            )
                ->join(
                    $taskInfoTable,
                    DBHelper::tableCol($taskInfoTable, TaskInfoConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskTable, TaskConstants::COL_TASK_INFO_ID)
                )
                ->join(
                    $userTable,
                    DBHelper::tableCol($userTable, UserConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskTable,TaskConstants::COL_USER_ID)
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
                    orientation: TaskDisplay::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_ORIENTATION)),
                    version: DBHelper::access($task, TaskConstants::COL_VERSION),
                    authorId:DBHelper::access($task,TaskConstants::COL_USER_ID),
                    authorName:DBHelper::access($task,$authorNameCol),
                    name:DBHelper::access($task,TaskInfoConstants::COL_NAME),
                    description:DBHelper::access($task,TaskInfoConstants::COL_DESCRIPTION)
                );
            }
            return $task;
        }
    }
}
