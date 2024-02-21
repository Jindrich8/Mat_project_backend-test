<?php

namespace App\Helpers {

    use App\Dtos\Defs\Errors\GeneralErrorDetails;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Defs\Types\Request\TimestampRange;
    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
    use App\Dtos\Defs\Types\Task\AuthorInfo;
    use App\Dtos\Defs\Types\Task\TaskDetailInfo;
    use App\Dtos\Errors\ErrorResponse;
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
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\Types\SaveTask;
    use App\Utils\DtoUtils;
    use App\Utils\TimeStampUtils;
    use App\Utils\Utils;
    use Carbon\Carbon;
    use Illuminate\Database\Query\Builder;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class TaskHelper
    {
        /**
         * @template T
         * @template TExerciseDto of ClassStructure
         * @template TGroupDto of ClassStructure
         * @param T[] $exercises
         * @param callable(T $exercise):TExerciseDto $exerciseToDto
         * @param callable(string[] $resources):TGroupDto $groupToDto
         * @param array<TGroupDto|TExerciseDto> &$entries
         */
        public static function getTaskEntries(
            int $taskInfoId,
            array $exercises,
            callable $exerciseToDto,
            callable $groupToDto,
            array &$entries,
            string $groupDtoEntriesKey = 'entries'
        ): void
        {
            $groupIdName = GroupConstants::COL_ID;
            $groups = DB::table(GroupConstants::TABLE_NAME)
                ->select([$groupIdName, GroupConstants::COL_START, GroupConstants::COL_LENGTH])
                ->where(GroupConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                ->orderBy(GroupConstants::COL_START, direction: 'asc')
                ->orderBy(GroupConstants::COL_LENGTH, direction: 'desc')
                ->get();

            $resources = DB::table(ResourceConstants::TABLE_NAME)
                ->select([
                    ResourceConstants::COL_GROUP_ID,
                    ResourceConstants::COL_CONTENT
                    ])
                ->whereIn(ResourceConstants::COL_GROUP_ID, $groups->keys())
                ->get();
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


            for ($exI = 0; ($exercise = array_shift($exercises)) !== null; ++$exI) {
                if ($exI === $exerciseEnd) {
                    $stackEntryKey = Utils::arrayFirstKey($stack);
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
                if ($exI === DBHelper::access($nextGroup, GroupConstants::COL_START)) {
                    $groupId = DBHelper::access($nextGroup, $groupIdName);
                    $groupDto = $groupToDto($resourcesByGroupId[$groupId] ?? []);
                    unset($resourcesByGroupId[$groupId]);

                    $dest[] = $groupDto;
                    $stack[] = [$exerciseEnd, &$dest];
                    $dest = &$groupDto->{$groupDtoEntriesKey};
                    $exerciseEnd = $exI + DBHelper::access($nextGroup, GroupConstants::COL_LENGTH);
                }
                $exerciseDto = $exerciseToDto($exercise);
                $dest[] = $exerciseDto;
            }
        }

        public static function getSavedTask(int $taskId, ?Carbon $localySavedTaskUtcTimestamp):SaveTask|null
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
                    $decodedSavedData =DtoUtils::importDto(
                        dto:TaskSaveContent::class,
                        json:$savedTask[SavedTaskConstants::COL_DATA],
                        table: $savedTaskTable,
                        column: SavedTaskConstants::COL_DATA,
                        id: [SavedTaskConstants::COL_USER_ID => $userId, SavedTaskConstants::COL_TASK_ID => $taskId],
                        wrapper:TaskSaveContent::EXERCISES,

                    );

                    return new SaveTask(
                        taskVersion:$savedTask[SavedTaskConstants::COL_TASK_VERSION],
                        content:$decodedSavedData
                    );
                }
            }
            return null;
        }

        public static function getInfo(BareTaskWAuthorName $task, ?TaskDetailInfo $info = null): TaskDetailInfo
        {
            return ($info ?? TaskDetailInfo::create())
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setAuthor(
                    AuthorInfo::create()
                        ->setName($task->authorName)
                )
                ->setDifficulty(DtoUtils::createOrderedEnumDto($task->difficulty))
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setMin(DtoUtils::createOrderedEnumDto($task->minClass))
                        ->setMax(DtoUtils::createOrderedEnumDto($task->maxClass))
                )
                ->setDescription($task->description)
                ->setVersion(ResponseHelper::translateIdForUser($task->version))
                ->setName($task->name);
        }

        /**
         * @param iterable<string,'ASC'|'DESC'> $filterToDirection
         * @param callable(string $filterName, 'ASC'|'DESC' $direction):bool $action
         * return false if order by is invalid
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
                    ErrorResponse::create()
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
         */
        public static function getTagsByTaskInfoId(array &$taskInfoIds)
        {
            /**
             * @var array<int,list<array{int,string}>> $tagsByTaskId
             */
            $tagsByTaskId = [];
            $tagTaskInfoTable = TagTaskInfoConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
            $tagTable = TagConstants::COL_ID;
            foreach (DB::table($tagTaskInfoTable)
                ->select([
                    DBHelper::colFromTableAsCol($tagTaskInfoTable, TagTaskInfoConstants::COL_TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskInfoTable, TagTaskInfoConstants::COL_TASK_INFO_ID),
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: TagConstants::COL_NAME,
                        as: 'name'
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
                    DBHelper::colFromTableAsCol($taskInfoTable,TagTaskInfoConstants::COL_TASK_INFO_ID),
                    $taskInfoIds
                    )
                ->get() as $tag) {
                /**
                 * @var int $taskInfoId
                 */
                $taskInfoId = $tag[TagTaskInfoConstants::COL_TASK_INFO_ID];
                $tagsByTaskId[$taskInfoId] ??= [];

                /**
                 * @var array{int,string}
                 */
                $tagData = [$tag[TagTaskInfoConstants::COL_TAG_ID] + 0, $tag['name'] . ''];
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
            $tagTable = TagConstants::COL_ID;
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
                    DBHelper::colExpression(
                        table: $tagTaskTable,
                        column: TagTaskInfoConstants::COL_TAG_ID
                    ),
                    '=',
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: TagConstants::COL_ID
                    )
                )
                ->where(
                    DBHelper::colExpression(
                        table: $tagTaskTable,
                        column: TagTaskInfoConstants::COL_TASK_INFO_ID
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

        /**
         * @param string[] &$tags tags ids from user (not translated)
         * @param Builder $builder
         * @return string[]
         * Returns all invalid tags
         */
        public static function filterTaskByTags(array &$tags, Builder $builder,string $taskInfoIdColName = TaskConstants::COL_TASK_INFO_ID): array
        {
            $areInvalid = false;
            $translatedTags = [];
            foreach ($tags as $tag) {
                $translatedId = RequestHelper::tryToTranslateId($tag);
                if ($translatedId === null) {
                    if (!$areInvalid) {
                        $areInvalid = true;
                        $translatedTags = [];
                    }
                    $translatedTags[] = $tag;
                } else if (!$areInvalid) {
                    $translatedTags[] = $translatedId;
                }
            }
            if (!$areInvalid) {
                $taskInfoIds = DB::table(TagTaskInfoConstants::TABLE_NAME)
                    ->select([TagTaskInfoConstants::COL_TASK_INFO_ID])
                    ->whereIn(TagTaskInfoConstants::COL_TAG_ID, $translatedTags)
                    ->get()->unique()->all();
                $builder->whereIn($taskInfoIdColName, $taskInfoIds);
                return [];
            }
            return $translatedTags;
        }

        public static function deleteActualExercisesByTaskInfo(int $taskInfoId): void
        {
            $typesByIds = DB::table(ExerciseConstants::TABLE_NAME)
            ->select([
                ExerciseConstants::COL_ID,
                ExerciseConstants::COL_EXERCISEABLE_TYPE
                ])
            ->where(ExerciseConstants::COL_TASK_INFO_ID,'=',$taskInfoId)
            ->pluck(ExerciseConstants::COL_EXERCISEABLE_TYPE,key:ExerciseConstants::COL_ID);

            $idsByType = [];
            foreach($typesByIds as $id => $type){
                $idsByType[$type][] =$id;
            }
            unset($typesByIds);
            foreach($idsByType as $type => $ids){
                if($ids){
                    ExerciseHelper::getHelper(ExerciseType::fromThrow($type))
                    ->delete($ids);
                }
            }
        }



        public static function filterTaskInfoByDifficultyRange(int $min, int $max, Builder $builder,bool $withPrefix = false): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskDifficulty::class);
            if (!$RangeError) {
                $column = $withPrefix ?
                DBHelper::tableCol(TaskInfoConstants::TABLE_NAME,TaskInfoConstants::COL_DIFFICULTY)
                : TaskInfoConstants::COL_DIFFICULTY;
                $builder->whereBetween($column, [$min, $max]);
            }
            return $RangeError;
        }

        public static function filterTaskInfoByClassRange(int $min, int $max, Builder $builder,bool $withPrefix = false): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskClass::class);
            if (!$RangeError) {
                $minClass = TaskInfoConstants::COL_MIN_CLASS;
                $maxClass = TaskInfoConstants::COL_MAX_CLASS;
                if($withPrefix){
                    $minClass = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME,$minClass);
                    $maxClass = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME,$maxClass);
                }
                $builder->where($minClass, '>=', $min);
                $builder->where($maxClass, '<=', $max);
            }
            return $RangeError;
        }

        public static function filterByModificationTimestamp(TimestampRange $range, Builder $builder): RangeError|null
        {
            $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                $builder->whereBetween(
                    DB::raw('COALESCE(' . TaskConstants::COL_UPDATED_AT . ',' . TaskConstants::COL_CREATED_AT . ')'),
                    [$minTimestamp, $maxTimestamp]
                );
                $rangeOrError = null;
            }
            return $rangeOrError;
        }

        public static function filterByCreationTimestamp(TimestampRange $range, Builder $builder): RangeError|null
        {
            return self::filterByTimestampColumn($range,$builder,column:TaskConstants::COL_CREATED_AT);
        }

        public static function filterByTimestampColumn(TimestampRange $range, Builder $builder,string $column):?RangeError{
             $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                $builder->whereBetween(
                    $column,
                    [$minTimestamp, $maxTimestamp]
                );
                $rangeOrError = null;
            }
            return $rangeOrError;
        }
    }
}
