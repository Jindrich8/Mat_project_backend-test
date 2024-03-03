<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\UserConstants;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\TableSpecificData\TaskDisplay;
    use Illuminate\Support\Facades\DB;

    class BareDetailTask
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
            public readonly string $authorName,
            public readonly int $authorId,
            public readonly int $version
        ) {
        }

        public static function tryFetchPublic(int $id)
        {
            $taskId = TaskConstants::COL_ID;
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
            $userTable = UserConstants::TABLE_NAME;

            $taskAuthorColName = 'authorName';
            $task = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable, $taskId),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_USER_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_VERSION),
                    DBHelper::colExpression(
                        table: $userTable,
                        column: UserConstants::COL_NAME,
                        as: $taskAuthorColName
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
                    DBHelper::tableCol($userTable,UserConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskTable, TaskConstants::COL_USER_ID)
                )
                ->where(
                    DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_ID),
                     '=',
                      $id
                    )
                ->where(
                    DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_IS_PUBLIC),
                '=',
                true)
                ->first();
                if($task){
                    $task  = new self(
                        id: DBHelper::access($task, TaskConstants::COL_ID),
                        taskInfoId: DBHelper::access($task, TaskConstants::COL_TASK_INFO_ID),
                        name: DBHelper::access($task, TaskInfoConstants::COL_NAME),
                        display: TaskDisplay::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_ORIENTATION)),
                        difficulty: TaskDifficulty::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_DIFFICULTY)),
                        minClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MIN_CLASS)),
                        maxClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MAX_CLASS)),
                        description: DBHelper::access($task, TaskInfoConstants::COL_DESCRIPTION),
                        authorName: DBHelper::access($task, $taskAuthorColName),
                        authorId: DBHelper::access($task, TaskConstants::COL_USER_ID),
                        version: DBHelper::access($task,TaskConstants::COL_VERSION)
                    );        
                }
                return $task;
        }
    }
}
