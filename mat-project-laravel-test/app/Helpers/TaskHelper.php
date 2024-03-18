<?php

namespace App\Helpers {

    use App\Dtos\Defs\Errors\GeneralErrorDetails;
    use App\Dtos\Defs\Types\Errors\EnumArrayError;
    use App\Dtos\Defs\Types\Errors\FieldError;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Defs\Types\Request\RequestOrderedEnumRange;
    use App\Dtos\Defs\Types\Request\TimestampRange;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Dtos\InternalTypes\TaskSaveContent;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\InternalException;
    use App\Helpers\BareModels\BareTaskWAuthorName;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\UserHelper;
    use App\ModelConstants\ExerciseConstants;
    use App\ModelConstants\GroupConstants;
    use App\ModelConstants\ResourceConstants;
    use App\ModelConstants\SavedTaskConstants;
    use App\ModelConstants\TagConstants;
    use App\ModelConstants\TagTaskInfoConstants;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskInfoConstants;
    use App\ModelConstants\TaskReviewConstants;
    use App\ModelConstants\TaskReviewTemplateConstants;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\Types\SaveTask;
    use App\Types\StopWatchTimer;
    use App\Types\TaskResTask;
    use App\Utils\DebugLogger;
    use App\Utils\DtoUtils;
    use App\Utils\TimeStampUtils;
    use App\Utils\Utils;
    use Carbon\Carbon;
    use Illuminate\Auth\AuthenticationException;
    use Illuminate\Database\Query\Builder;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class TaskHelper
    {

        /**
         * @param int[] $taskInfoIds
         * @return (array{0:int,1:float})[]
         * @throws AuthenticationException
         */
        public static function getTaskReviewIdsAndScoreByTaskInfoId(array $taskInfoIds)
        {
            $userId = UserHelper::getUserId();
            $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
            $taskReviewTable = TaskReviewConstants::TABLE_NAME;

            $taskReviews = DB::table($taskReviewTable)
                ->select([
                    DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE)
                ])
                ->join(
                    $taskReviewTemplateTable,
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                    '=',
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID)
                )
                ->whereIn(
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                    $taskInfoIds
                )->where(
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                    '=',
                    $userId
                )
                ->get();
            $res = [];
            while (($review = $taskReviews->pop()) !== null) {
                $taskInfoId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_INFO_ID);
                $reviewId = DBHelper::access($review, TaskReviewConstants::COL_ID);
                $score = DBHelper::access($review, TaskReviewConstants::COL_SCORE);
                $res[$taskInfoId] = [$reviewId, $score];
            }
            return $res;
        }

        /**
         * @template T
         * @template TExerciseDto of ClassStructure
         * @template TGroupDto of ClassStructure
         * @param T[] $exercises
         * @param callable(T $exercise,int $index):TExerciseDto $exerciseToDto
         * @param callable(string[] $resources):TGroupDto $groupToDto
         * @param array<TGroupDto|TExerciseDto> &$entries
         */
        public static function getTaskEntries(
            int $taskSourceId,
            array $exercises,
            callable $exerciseToDto,
            callable $groupToDto,
            array &$entries,
            string $groupDtoEntriesKey = 'entries'
        ): void {
            $groupIdName = GroupConstants::COL_ID;

            $groups = null;
            $resources = null;
            StopWatchTimer::run("getTaskEntries - fetching", function ()
            use (&$groups, &$resources, $taskSourceId, $groupIdName) {

                $groups = DB::table(GroupConstants::TABLE_NAME)
                    ->select([$groupIdName, GroupConstants::COL_START, GroupConstants::COL_LENGTH])
                    ->where(GroupConstants::COL_TASK_SOURCE_ID, '=', $taskSourceId)
                    ->orderBy(GroupConstants::COL_START, direction: 'asc')
                    ->orderBy(GroupConstants::COL_LENGTH, direction: 'desc')
                    ->get()
                    ->keyBy(fn ($value, $key) => DBHelper::access($value, $groupIdName));

                $resources = DB::table(ResourceConstants::TABLE_NAME)
                    ->select([
                        ResourceConstants::COL_GROUP_ID,
                        ResourceConstants::COL_CONTENT
                    ])
                    ->whereIn(ResourceConstants::COL_GROUP_ID, $groups->keys())
                    ->get();
            });
            /**
             * @var \Illuminate\Support\Collection $groups
             * @var \Illuminate\Support\Collection $resources
             */
            StopWatchTimer::run('getTaskEntries - processing', function ()
            use ($resources, $groups, $groupDtoEntriesKey, $groupIdName, &$entries, $exercises, $groupToDto, $exerciseToDto) {
                /**
                 * @var array<mixed,string[]> $resourcesByGroupId
                 */
                $resourcesByGroupId = [];
                while (($resource = $resources->pop()) !== null) {
                    $groupId = DBHelper::access($resource, ResourceConstants::COL_GROUP_ID);
                    /**
                     * @var string $content
                     */
                    $content = DBHelper::access($resource, ResourceConstants::COL_CONTENT);
                    $resourcesByGroupId[$groupId][] = $content;
                }



                /**
                 * array{exerciseEnd,entriesArray}
                 * @var array<array{0:int,1:&array<TExerciseDto|TGroupDto>}> $stack
                 * @noinspection PhpVarTagWithoutVariableNameInspection
                 */
                $stack = [];
                $dest = &$entries;
                $exercisesCount = count($exercises);
                $exerciseEnd = $exercisesCount;
                $nextGroup = $groups->shift();
                $nextGroupColStart = $nextGroup ? 
                DBHelper::access($nextGroup, GroupConstants::COL_START)
                : null;


                for ($exI = 0; ($exercise = array_shift($exercises)) !== null; ++$exI) {
                    if ($exI === $exerciseEnd) {
                        $stackEntryKey = array_key_first($stack);
                        if ($stackEntryKey === null) {
                            throw new InternalException(
                                message: "Stack should not be empty yet, because we still have exercises to process.",
                                context: [
                                    'exercises' => $exercises,
                                    'exerciseIndex' => $exI,
                                    'stack' => $stack,
                                    'destination' => $dest
                                ]
                            );
                        }

                        [$exerciseEnd, &$dest] =  $stack[$stackEntryKey];
                        unset($stack[$stackEntryKey]);
                    }
                    if ($exI === $nextGroupColStart && $nextGroup) {
                        $groupId = DBHelper::access($nextGroup, $groupIdName);
                        $groupDto = $groupToDto($resourcesByGroupId[$groupId] ?? []);
                        unset($resourcesByGroupId[$groupId]);

                        $dest[] = $groupDto;
                        $stack[] = [$exerciseEnd, &$dest];
                        $dest = &$groupDto->{$groupDtoEntriesKey};
                        $exerciseEnd = $exI + DBHelper::access($nextGroup, GroupConstants::COL_LENGTH);

                        $nextGroup = $groups->shift();
                        $nextGroupColStart = null;
                        if ($nextGroup) {
                            $nextGroupColStart = DBHelper::access($nextGroup, GroupConstants::COL_START);
                        }
                    }
                    $exerciseDto = $exerciseToDto($exercise, $exI);
                    $dest[] = $exerciseDto;
                }
            });
        }

        public static function getSavedTask(int $taskId, ?Carbon $localySavedTaskUtcTimestamp): SaveTask|null
        {
            $userId = UserHelper::tryGetUserId();
            if ($userId !== null) {
                $savedTaskTable = SavedTaskConstants::TABLE_NAME;
                $builder = DB::table($savedTaskTable)
                    ->select([
                        SavedTaskConstants::COL_DATA,
                        SavedTaskConstants::COL_TASK_VERSION
                    ])
                    ->where(SavedTaskConstants::COL_USER_ID, '=', $userId)
                    ->where(SavedTaskConstants::COL_TASK_ID, '=', $taskId);
                if ($localySavedTaskUtcTimestamp) {
                    TimeStampUtils::timestampToUtc($localySavedTaskUtcTimestamp);
                    $builder = $builder
                        ->where(SavedTaskConstants::COL_UPDATED_AT, '>', $localySavedTaskUtcTimestamp, boolean: 'or')
                        ->where(SavedTaskConstants::COL_CREATED_AT, '>', $localySavedTaskUtcTimestamp);
                }
                $savedTask = $builder->first();
                if ($savedTask) {
                    $decodedSavedData = DtoUtils::importDto(
                        dto: TaskSaveContent::class,
                        json: $savedTask[SavedTaskConstants::COL_DATA],
                        table: $savedTaskTable,
                        column: SavedTaskConstants::COL_DATA,
                        id: [SavedTaskConstants::COL_USER_ID => $userId, SavedTaskConstants::COL_TASK_ID => $taskId],
                        wrapper: TaskSaveContent::EXERCISES,

                    );

                    return new SaveTask(
                        taskVersion: $savedTask[SavedTaskConstants::COL_TASK_VERSION],
                        content: $decodedSavedData
                    );
                }
            }
            return null;
        }

        /**
         * @param iterable<string,'ASC'|'DESC'> $filterToDirection
         * @param callable(string $filterName, 'ASC'|'DESC' $direction):bool $action
         * return false if order by is invalid
         * @throws ApplicationException
         */
        public static function distinctOrderBy(iterable $filterToDirection, callable $action)
        {

            /**
             * @var array<string,true> $usedOrderByFilters
             */
            $usedOrderByFilters = [];

            /**
             * @var array<string,string> $usedOrderByFilters
             * array<filterName,'ASC'|'DESC'>
             */
            $duplicateOrderByFilters = [];

            foreach ($filterToDirection as $filterName => $direction) {
                if (!Utils::arrayHasKey($usedOrderByFilters, $filterName)) {
                    $usedOrderByFilters[$filterName] = true;
                    $success = $action($filterName, $direction);
                    if (!$success) {
                        // Unsupported order by filter - this should not ever happen, because this validation is in schema
                        // If this branch is taken, it means, that:
                        // 1. no schema validation specified
                        // 2. schema validation failed
                        // 3. schema or application logic was updated, but this code was not
                        throw new InternalException(
                            message: "Unsupported order by filter '{$filterName}' with direction '{$direction}'."
                                . "This error signalize schema and application logic integrity error, see comment above this throw statement.",
                            context: [
                                'filterName' => $filterName,
                                'direction' => $direction
                            ]
                        );
                    }
                } else {
                    $duplicateOrderByFilters[$filterName] = $direction;
                }
            }

            if ($duplicateOrderByFilters) {
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
                    ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("Bad request.")
                                ->setDescription("Please correct request fields.")
                        )
                        ->setDetails(GeneralErrorDetails::create()
                            ->setErrorData([
                                'duplicateOrderByFilters' => $duplicateOrderByFilters
                            ]))
                );
            }
        }


        /**
         * @param int[] &$taskInfoIds
         * @return array<int,list<array{int,string}>> $tagsByTaskId
         */
        public static function getTagsByTaskInfoId(array &$taskInfoIds): array
        {
            /**
             * @var array<int,list<array{int,string}>> $tagsByTaskId
             */
            $tagsByTaskId = [];
            $tagTaskInfoTable = TagTaskInfoConstants::TABLE_NAME;
            $tagTable = TagConstants::TABLE_NAME;
            $tagNameCol = 'name';
            foreach (DB::table($tagTaskInfoTable)
                ->select([
                    DBHelper::colFromTableAsCol($tagTaskInfoTable, TagTaskInfoConstants::COL_TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskInfoTable, TagTaskInfoConstants::COL_TASK_INFO_ID),
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: TagConstants::COL_NAME,
                        as: $tagNameCol
                    )
                ])
                ->join(
                    $tagTable,
                    DBHelper::colExpression(
                        table: $tagTaskInfoTable,
                        column: TagTaskInfoConstants::COL_TAG_ID
                    ),
                    '=',
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: TagConstants::COL_ID
                    )
                )
                ->whereIn(
                    DBHelper::tableCol($tagTaskInfoTable, TagTaskInfoConstants::COL_TASK_INFO_ID),
                    $taskInfoIds
                )
                ->get() as $tag) {
                /**
                 * @var int $taskInfoId
                 */
                $taskInfoId = DBHelper::access($tag, TagTaskInfoConstants::COL_TASK_INFO_ID);
                $tagsByTaskId[$taskInfoId] ??= [];

                /**
                 * @var array{int,string} $tagData
                 */
                $tagData = [
                    DBHelper::access($tag, TagTaskInfoConstants::COL_TAG_ID) + 0,
                    DBHelper::access($tag, $tagNameCol) . ''
                ];
                $tagsByTaskId[$taskInfoId][] = $tagData;
            }
            return $tagsByTaskId;
        }

        /**
         * Returns tag names indexed by their ids
         */
        public static function getTaskInfoTags(int $taskInfoId)
        {
            $tagTaskTable = TagTaskInfoConstants::TABLE_NAME;
            $tagTable = TagConstants::TABLE_NAME;
            $tags = DB::table($tagTaskTable)
                ->select([
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskInfoConstants::COL_TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskInfoConstants::COL_TASK_INFO_ID),
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: TagConstants::COL_NAME,
                        as: 'name'
                    )
                ])
                ->join(
                    $tagTable,
                    DBHelper::tableCol(
                        $tagTaskTable,
                        TagTaskInfoConstants::COL_TAG_ID
                    ),
                    '=',
                    DBHelper::tableCol(
                        $tagTable,
                        TagConstants::COL_ID
                    )
                )
                ->where(
                    DBHelper::tableCol(
                        $tagTaskTable,
                        TagTaskInfoConstants::COL_TASK_INFO_ID
                    ),
                    '=',
                    $taskInfoId
                )
                ->get();

            return $tags->mapWithKeys(function ($tag, $key) {
                return [
                    DBHelper::access($tag, TagTaskInfoConstants::COL_TAG_ID) + 0
                    => DBHelper::access($tag, 'name') . ''
                ];
            });
        }

        public static function setTagsToTaskResTask(array $tags, TaskResTask $task): EnumArrayError|null
        {
            $error = null;
            if (($invalidTags = self::validateTaskTags($tags, $translatedTags))) {
                $error =   EnumArrayError::create()
                    ->setMessage("Invalid ids specified.");
            } else {
                $task->tagIds = $translatedTags ?? [];
            }
            return $error;
        }

        public static function setDifficultyToTaskResTask(int $difficulty, TaskResTask $task): FieldError|null
        {
            $error = null;
            if (!($task->difficulty = TaskDifficulty::tryFrom($difficulty))) {
                $error = FieldError::create()
                    ->setMessage("Invalid difficulty specified");
            }
            return $error;
        }

        public static function setClassRangeToTaskResTask(RequestOrderedEnumRange $classRange, TaskResTask $task): RangeError|null
        {
            $error = null;
            $rangeErrorOrEnums = DtoHelper::validateEnumRange($classRange->min, $classRange->max, TaskClass::class);
            if ($rangeErrorOrEnums instanceof RangeError) {
                $error = $rangeErrorOrEnums;
            } else {
                [$task->minClass, $task->maxClass] = $rangeErrorOrEnums;
            }
            return $error;
        }

        public static function addExistingTaskResTaskDataToTaskInfoBindings(array &$taskInfoBindings, TaskResTask $task)
        {
            if (isset($task->description)) {
                $taskInfoBindings[TaskInfoConstants::COL_DESCRIPTION] = $task->description;
            }
            if (isset($task->display)) {
                $taskInfoBindings[TaskInfoConstants::COL_ORIENTATION] = $task->display->value;
            }
            if (isset($task->difficulty)) {
                $taskInfoBindings[TaskInfoConstants::COL_DIFFICULTY] = $task->difficulty->value;
            }
            if (isset($task->minClass)) {
                $taskInfoBindings[TaskInfoConstants::COL_MIN_CLASS] = $task->minClass->value;
            }
            if (isset($task->maxClass)) {
                $taskInfoBindings[TaskInfoConstants::COL_MAX_CLASS] = $task->maxClass->value;
            }
        }

        public static function insertNewTaskInfoGetId(array $taskInfoBindings,int $taskInfoId){
            $insertColumns = [
                TaskInfoConstants::COL_DESCRIPTION,
                TaskInfoConstants::COL_MIN_CLASS,
                TaskInfoConstants::COL_MAX_CLASS,
                TaskInfoConstants::COL_DIFFICULTY,
                TaskInfoConstants::COL_ORIENTATION,
                TaskInfoConstants::COL_TASK_SOURCE_ID
            ];
            DebugLogger::debug(self::class."::insertNewTaskInfoGetId",[
                'taskInfoBindings' => $taskInfoBindings,
                'taskInfoId' => $taskInfoId
            ] );
           $newTaskInfoId = DBHelper::insertFromSameByIdSingleWConstantsGetId(
                tableName:TaskInfoConstants::TABLE_NAME,
           insertColumns:$insertColumns,
           values:$taskInfoBindings,
           primaryKeyName:TaskInfoConstants::COL_ID,
           primaryKeyValue:$taskInfoId
            );
            return $newTaskInfoId;
        }

        /**
         * @param string[] $tags tags ids from user (not translated)
         * @param ?int[] &$translatedTags
         * @return string[]
         * Returns all invalid tags
         */
        public static function validateTaskTags(array $tags, ?array &$translatedTags): array
        {
            $areInvalid = false;
            $invalidTags = [];
            $translatedTags = [];
            foreach ($tags as $tag) {
                $translatedId = RequestHelper::tryToTranslateId($tag);
                if ($translatedId === null) {
                    if (!$areInvalid) {
                        $areInvalid = true;
                        $translatedTags = [];
                    }
                    $invalidTags[] = $tag;
                } else if (!$areInvalid) {
                    $translatedTags[] = $translatedId;
                }
            }
            return $invalidTags;
        }

        /**
         * @param string[] &$tags tags ids from user (not translated)
         * @param Builder $builder
         * @param string $taskInfoIdColName
         * @return string[]
         * Returns all invalid tags
         */
        public static function filterTaskByTags(array &$tags, Builder $builder, string $taskInfoIdColName = TaskConstants::COL_TASK_INFO_ID, bool $hasAll = false): array
        {
            $invalidTags = self::validateTaskTags($tags, $translatedTags);
            if (!$invalidTags && $translatedTags) {
                if (!$hasAll) {
                    // Get task info ids that have at least one of the specified tags
                    $builder->whereIn($taskInfoIdColName, function (Builder $query)
                    use ($translatedTags) {
                        $query->select([TagTaskInfoConstants::COL_TASK_INFO_ID])
                            ->from(TagTaskInfoConstants::TABLE_NAME)
                            ->whereIn(TagTaskInfoConstants::COL_TAG_ID, $translatedTags);
                    });
                } else {
                    // Get task info ids that have all specified tags
                    $builder->whereIn($taskInfoIdColName, function (Builder $query)use($translatedTags){
                        $query->select([TagTaskInfoConstants::COL_TASK_INFO_ID])
                        ->from(TagTaskInfoConstants::TABLE_NAME)
                        ->whereIn(TagTaskInfoConstants::COL_TAG_ID, $translatedTags)
                        ->groupBy(TagTaskInfoConstants::COL_TASK_INFO_ID)
                        ->havingRaw("COUNT(*) >= ?",[count($translatedTags)]);
                    });
                }
            }
            return $invalidTags;
        }

        public static function deleteActualExercisesByTaskSource(int $taskSourceId): void
        {
            $typesByIds = DB::table(ExerciseConstants::TABLE_NAME)
                ->select([
                    ExerciseConstants::COL_ID,
                    ExerciseConstants::COL_EXERCISEABLE_TYPE
                ])
                ->where(ExerciseConstants::COL_TASK_SOURCE_ID, '=', $taskSourceId)
                ->pluck(ExerciseConstants::COL_EXERCISEABLE_TYPE, key: ExerciseConstants::COL_ID);

            $idsByType = [];
            foreach ($typesByIds as $id => $type) {
                $idsByType[$type][] = $id;
            }
            unset($typesByIds);
            foreach ($idsByType as $type => $ids) {
                if ($ids) {
                    ExerciseHelper::getHelper(ExerciseType::fromThrow($type))
                        ->delete($ids);
                }
            }
        }


        /**
         * @return RangeError|null
         */
        public static function filterTaskInfoByDifficultyRange(int $min, int $max, Builder $builder, bool $withPrefix = false): RangeError|null
        {
            $rangeErrorOrEnums = DtoHelper::validateEnumRange($min, $max, TaskDifficulty::class);
            if (!($rangeErrorOrEnums instanceof RangeError)) {
                $column = $withPrefix ?
                    DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_DIFFICULTY)
                    : TaskInfoConstants::COL_DIFFICULTY;
                if ($min <= TaskDifficulty::EASY->value) {
                    if ($max < TaskDifficulty::HARD->value) {
                        $builder->where($column, '<=', $max);
                    }
                } else if ($max >= TaskDifficulty::HARD->value) {
                    if ($min > TaskDifficulty::EASY->value) {
                        $builder->where($column, '>=', $min);
                    }
                } else {
                    $builder->whereBetween($column, [$min, $max]);
                }
                $rangeErrorOrEnums = null;
            }
            return $rangeErrorOrEnums;
        }

        /**
         * @return RangeError|null
         */
        public static function filterTaskInfoByClassRange(int $min, int $max, Builder $builder, bool $withPrefix = false): RangeError|null
        {
            $rangeErrorOrEnums = DtoHelper::validateEnumRange($min, $max, TaskClass::class);
            if (!($rangeErrorOrEnums instanceof RangeError)) {
                $minClass = TaskInfoConstants::COL_MIN_CLASS;
                $maxClass = TaskInfoConstants::COL_MAX_CLASS;
                if ($withPrefix) {
                    $minClass = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, $minClass);
                    $maxClass = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, $maxClass);
                }
                if ($min > TaskClass::ZS_1->value) {
                    $builder->where($minClass, '>=', $min);
                }
                if ($max < TaskClass::AFTER_SS->value) {
                    $builder->where($maxClass, '<=', $max);
                }
                $rangeErrorOrEnums = null;
            }
            return $rangeErrorOrEnums;
        }

        public static function filterByModificationTimestamp(TimestampRange $range, Builder $builder,bool $withPrefix = false): RangeError|null
        {
            $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                $updatedAt = TaskConstants::COL_UPDATED_AT;
                $createdAt = TaskConstants::COL_CREATED_AT;
                if($withPrefix){
                    $updatedAt = DBHelper::tableCol(TaskConstants::TABLE_NAME,$updatedAt);
                    $createdAt = DBHelper::tableCol(TaskConstants::TABLE_NAME,$createdAt);
                }
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                if($minTimestamp && $maxTimestamp){
                $builder->whereBetween(
                    DB::raw('COALESCE(' . $updatedAt . ',' . $createdAt . ')'),
                    [$minTimestamp, $maxTimestamp]
                );
            }
            else if($minTimestamp){
                $builder->where(
                    DB::raw('COALESCE(' . $updatedAt . ',' . $createdAt . ')'),
                    '>=',
                    $minTimestamp
                );
            }
            else if($maxTimestamp){
                $builder->where(
                    DB::raw('COALESCE(' . $updatedAt . ',' . $createdAt . ')'),
                    '<=',
                    $maxTimestamp
                );
            }
                $rangeOrError = null;
            }
            return $rangeOrError;
        }

        public static function filterByCreationTimestamp(TimestampRange $range, Builder $builder,bool $withPrefix = false): RangeError|null
        {
            return self::filterByTimestampColumn(
                $range, 
                $builder, 
                column: $withPrefix ? 
                DBHelper::tableCol(TaskConstants::TABLE_NAME,TaskConstants::COL_CREATED_AT)
                 : TaskConstants::COL_CREATED_AT
                );
        }

        public static function filterByTimestampColumn(TimestampRange $range, Builder $builder, string $column): ?RangeError
        {
            $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                if($minTimestamp && $maxTimestamp){
                $builder->whereBetween(
                    $column,
                    [
                        TimeStampUtils::timestampToString($minTimestamp),
                        TimeStampUtils::timestampToString($maxTimestamp)
                    ]
                );
            }
            else if($minTimestamp){
                $builder->where($column,'>=',TimeStampUtils::timestampToString($minTimestamp));
            }
            else if($maxTimestamp){
                $builder->where($column,'<=',TimeStampUtils::timestampToString($maxTimestamp));
            }
                $rangeOrError = null;
            }
            return $rangeOrError;
        }
    }
}
