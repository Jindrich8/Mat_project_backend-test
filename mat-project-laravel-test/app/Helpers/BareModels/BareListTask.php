<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\UserConstants;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use Illuminate\Support\Facades\DB;

    class BareListTask
    {
        public function __construct(
            public readonly int $id,
            public readonly int $taskInfoId,
            public readonly string $name,
            public readonly TaskDifficulty $difficulty,
            public readonly TaskClass $minClass,
            public readonly TaskClass $maxClass,
            public readonly string $authorName,
            public readonly int $authorId
        ){}

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetchPublic(callable $modifyQuery)
        {
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
            $userTable = UserConstants::TABLE_NAME;
            $authorNameCol = 'authorName';

            $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_USER_ID),
                    DBHelper::colExpression(
                        table:$userTable, 
                        column:UserConstants::COL_NAME,
                        as:$authorNameCol)
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
                    DBHelper::tableCol($userTable, TaskInfoConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskTable, TaskConstants::COL_USER_ID)
                )
                ->where(
                    DBHelper::tableCol($taskTable, TaskConstants::COL_IS_PUBLIC),
                    '=',
                    true
                );
                $modifyQuery($builder);

               $tasks = $builder->get()
                ->map(fn($task) =>
                     new self(
                        id: DBHelper::access($task, TaskConstants::COL_ID),
                        taskInfoId: DBHelper::access($task, TaskConstants::COL_TASK_INFO_ID),
                        name: DBHelper::access($task, TaskInfoConstants::COL_NAME),
                        authorName:DBHelper::access($task,$authorNameCol),
                        authorId: DBHelper::access($task,TaskConstants::COL_USER_ID),
                        minClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MIN_CLASS)),
                        maxClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MAX_CLASS)),
                        difficulty:TaskDifficulty::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_DIFFICULTY))
                    )
                );
            return $tasks;
        }
    }
}