<?php

namespace App\Helpers\BareModels {

    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\UserHelper;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\TaskReviewConstants;
    use App\ModelConstants\TaskReviewTemplateConstants;
    use App\ModelConstants\UserConstants;
    use App\Models\Task;
    use App\Models\User;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\TableSpecificData\TaskDisplay;
    use App\Utils\Utils;
    use Illuminate\Database\Query\Builder;
    use DB;
    use Illuminate\Database\Query\JoinClause;

    class BareTaskWAuthorName
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
            public readonly bool $isPublic,
            public readonly int $version,
            public readonly int $userId,
            public readonly ?int $taskReviewId
        ) {
        }

        /**
         *
         */
        public static function fromRecord(array|object $task, string $authorNameColName, string $taskReviewIdColName)
        {
            return new self(
                id: DBHelper::access($task, TaskConstants::COL_ID),
                taskInfoId: DBHelper::access($task, TaskConstants::COL_TASK_INFO_ID),
                name: DBHelper::access($task, TaskInfoConstants::COL_NAME),
                display: TaskDisplay::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_ORIENTATION)),
                difficulty: TaskDifficulty::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_DIFFICULTY)),
                minClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MIN_CLASS)),
                maxClass: TaskClass::fromThrow(DBHelper::access($task, TaskInfoConstants::COL_MAX_CLASS)),
                description: DBHelper::access($task, TaskInfoConstants::COL_DESCRIPTION),
                authorName: DBHelper::access($task, $authorNameColName),
                isPublic: DBHelper::access($task, TaskConstants::COL_IS_PUBLIC),
                version: DBHelper::access($task, TaskConstants::COL_VERSION),
                userId: DBHelper::access($task, TaskConstants::COL_USER_ID),
                taskReviewId: DBHelper::tryToAccess($task, $taskReviewIdColName,null)
            );

        }

        public static function tryFetchById(int $id, bool $publicOnly = true,bool $sharedLock = false): self|null
        {
            return self::tryFetch(function (Builder $builder) use ($id, $publicOnly,$sharedLock) {
                $builder->where(
                    DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_ID),
                     '=',
                      $id
                    );
                if ($publicOnly) {
                    $builder->where(
                        DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_IS_PUBLIC),
                         '=',
                          true
                        );
                }
                if($sharedLock){
                $builder->sharedLock();
                }
            })->first(default: null);
        }

        /**
         * @param callable(Builder $builder):void $modifyQuery
         */
        public static function tryFetch(callable $modifyQuery)
        {
            $taskId = TaskConstants::COL_ID;
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
            $userTable = UserConstants::TABLE_NAME;
            $taskReviewTable = TaskReviewConstants::TABLE_NAME;
            $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;

            $taskAuthorColName = 'authorName';
            $taskReviewIdColName = 'taskReviewId';
            $builder = DB::table($taskTable)->select(
                [
                    DBHelper::colFromTableAsCol($taskTable, $taskId),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_IS_PUBLIC),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_VERSION),
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_USER_ID),
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
                ->leftJoin(
                    $taskReviewTemplateTable,
                    DBHelper::tableCol($taskReviewTemplateTable,TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                    '=',
                    DBHelper::tableCol($taskTable, TaskConstants::COL_TASK_INFO_ID)
                );
                $userId = UserHelper::tryGetUserId();
                if($userId !== null){
                    $builder->addSelect(DBHelper::colExpression(
                        table: TaskReviewConstants::TABLE_NAME,
                        column: TaskReviewConstants::COL_ID,
                        as: $taskReviewIdColName
                    ))
                    ->leftJoin(
                        $taskReviewTable,
                        function (JoinClause $join)use($taskReviewTable,$taskReviewTemplateTable) {
                            $join->on(
                                DBHelper::tableCol($taskReviewTable,TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                                '=',
                                DBHelper::tableCol($taskReviewTemplateTable,TaskReviewTemplateConstants::COL_ID)
                            )
                                ->on(
                                    DBHelper::tableCol($taskReviewTable,TaskReviewConstants::COL_USER_ID),
                                    '=',
                                    DB::raw(UserHelper::getUserId())
                                );
                        }
                    );
                }
              
            $modifyQuery($builder);
            $tasks = $builder->get();

            $bareTasks = $tasks->map(
                static fn ($task) =>
                self::fromRecord(
                    task: $task,
                    authorNameColName: $taskAuthorColName,
                    taskReviewIdColName: $taskReviewIdColName
                )
            );
            return $bareTasks;
        }
    }
}
