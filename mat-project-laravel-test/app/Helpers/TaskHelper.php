<?php

namespace App\Helpers {

    use App\Dtos\Defs\Errors\GeneralErrorDetails;
    use App\Dtos\Defs\Types\Errors\InvalidBoundsError;
    use App\Dtos\Defs\Types\Errors\RangeError;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Defs\Types\Request\TimestampRange;
    use App\Dtos\Defs\Types\Response\ResponseClassRange;
    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
    use App\Dtos\Defs\Types\Task\AuthorInfo;
    use App\Dtos\Defs\Types\Task\TaskDetailInfo;
    use App\Dtos\Defs\Types\Task\TaskDetailInfoAuthor;
    use App\Dtos\Errors\ErrorResponse;
    use App\Dtos\InternalTypes\TaskSaveContent;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\ConversionException;
    use App\Exceptions\InternalException;
    use App\Helpers\BareModels\BareTaskWAuthorName;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\DBJsonHelper;
    use App\ModelConstants\GroupConstants;
    use App\ModelConstants\ResourceConstants;
    use App\ModelConstants\SavedTaskConstants;
    use App\ModelConstants\TagConstants;
    use App\ModelConstants\TagTaskConstants;
    use App\ModelConstants\TaskConstants;
    use App\Models\Group;
    use App\Models\Resource;
    use App\Models\SavedTask;
    use App\Models\Tag;
    use App\Models\TagTask;
    use App\Models\Task;
    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\Types\SaveTask;
    use App\Utils\DtoUtils;
    use App\Utils\TimeStampUtils;
    use App\Utils\Utils;
    use Carbon\Carbon;
    use DateTime;
    use Illuminate\Database\Query\Builder;
    use Illuminate\Http\Response;
    use Illuminate\Support\Carbon as SupportCarbon;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use IntBackedEnum;

    class TaskHelper
    {
        /**
         * @template T 
         * @template TExerciseDto of ClassStructure
         * @template TGroupDto of ClassStructure
         * @param int $taskId
         * @param T[] $exercises
         * @param callable(T $exercise):TExerciseDto $exerciseToDto
         * @param callable(string[] $resources):TGroupDto $groupToDto
         * @param array &$entries
         */
        public static function getTaskEntries(
            int $taskId,
            array $exercises,
            callable $exerciseToDto,
            callable $groupToDto,
            array &$entries
        ) {
            $groupIdName = Group::getPrimaryKeyName();
            $groups = DB::table(GroupConstants::TABLE_NAME)
                ->select([$groupIdName, GroupConstants::COL_START, GroupConstants::COL_LENGTH])
                ->where(GroupConstants::COL_TASK_ID, '=', $taskId)
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
                /**
                 * @var mixed $groupId
                 */
                $groupId = DBHelper::access($resource, ResourceConstants::COL_GROUP_ID);
                /**
                 * @var string $content
                 */
                $content = DBHelper::access($resource, ResourceConstants::COL_CONTENT);
                $resourcesByGroupId[$groupId][] = $content;
            }



            /**
             * array{exerciseEnd,entriesArray}
             * @var array<array{0:int,1:&array<Take\Response\DefsGroup|Take\Response\DefsExercise>}> $stack
             */
            $stack = [];
            /**
             * @var (Take\Response\DefsGroup|Take\Response\DefsExercise)[] &$dest
             */
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
                    $dest = &$groupDto->entries;
                    $exerciseEnd = $exI + DBHelper::access($nextGroup, GroupConstants::COL_LENGTH);
                }
                $exerciseDto = $exerciseToDto($exercise);
                $dest[] = $exerciseDto;
            }
        }

        public static function getSavedTask(int $taskId, ?Carbon $localySavedTaskUtcTimestamp):SaveTask|null
        {
            $user = Auth::user();
            if ($user !== null) {
                TimeStampUtils::timestampToUtc($localySavedTaskUtcTimestamp);
                $savedTaskTable = SavedTask::getTableName();
                $builder = DB::table($savedTaskTable)
                    ->select([
                        SavedTaskConstants::COL_DATA, 
                        SavedTaskConstants::COL_TASK_VERSION
                        ])
                    ->where(SavedTaskConstants::COL_USER_ID, '=', $user->id)
                    ->where(SavedTaskConstants::COL_TASK_ID, '=', $taskId);
                if ($localySavedTaskUtcTimestamp) {
                    $builder = $builder
                        ->where(SavedTaskConstants::COL_UPDATED_AT, '>', $localySavedTaskUtcTimestamp, boolean: 'or')
                        ->where(SavedTaskConstants::COL_CREATED_AT, '>', $localySavedTaskUtcTimestamp);
                }
                $savedTask = $builder->first();
                if ($savedTask) {
                    $savedTaskData = $savedTask[SavedTaskConstants::COL_DATA];
                    $taskVersion = $savedTaskData[SavedTaskConstants::COL_TASK_VERSION];
                    $decodedSavedData = TaskSaveContent::import(
                        (object)[
                            TaskSaveContent::EXERCISES =>
                            DBJsonHelper::decode(
                                $savedTaskData,
                                table: $savedTaskTable,
                                column: SavedTaskConstants::COL_DATA,
                                id: [SavedTaskConstants::COL_USER_ID => $user->id, SavedTaskConstants::COL_TASK_ID => $taskId]
                            )
                        ]
                    );
                    return new SaveTask(
                        taskVersion:$taskVersion,
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


        public static function getTagsByTaskId()
        {
            /**
             * @var array<int,list<array{int,string}>> $tagsByTaskId
             */
            $tagsByTaskId = [];
            $tagTaskTable = TagTask::getTableName();
            $tagTable = Tag::getPrimaryKeyName();
            foreach (DB::table($tagTaskTable)
                ->select([
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskConstants::COL_TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskConstants::COL_TASK_ID),
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
                        column: TagTaskConstants::COL_TAG_ID
                    ),
                    '=',
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: Tag::getPrimaryKeyName()
                    )
                )
                ->get() as $tag) {
                /**
                 * @var int $taskId
                 */
                $taskId = $tag[TagTaskConstants::COL_TASK_ID];
                $tagsByTaskId[$taskId] ??= [];

                /**
                 * @var array{int,string}
                 */
                $tagData = [$tag[TagTaskConstants::COL_TAG_ID] + 0, $tag['name'] . ''];
                $tagsByTaskId[$taskId][] = $tagData;
            }
            return $tagsByTaskId;
        }

        /**
         * Returns tag names indexed by their ids
         */
        public static function getTaskTags(int $taskId)
        {
            $tagTaskTable = TagTask::getTableName();
            $tagTable = Tag::getPrimaryKeyName();
            $tags = DB::table($tagTaskTable)
                ->select([
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskConstants::COL_TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTaskConstants::COL_TASK_ID),
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
                        column: TagTaskConstants::COL_TAG_ID
                    ),
                    '=',
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: Tag::getPrimaryKeyName()
                    )
                )
                ->where(
                    DBHelper::colExpression(
                        table: $tagTaskTable,
                        column: TagTaskConstants::COL_TASK_ID
                    ),
                    '=',
                    $taskId
                )
                ->get();

            $access = null;
            return $tags->mapWithKeys(function ($tag, $key) use ($access) {
                $access ??= Utils::getAccessor($tag);
                return [$access($tag, TagTaskConstants::COL_TAG_ID) + 0 => $access($tag, 'name') . ''];
            });
        }

        /**
         * @param string[] &$tags tags ids from user (not translated)
         * @param Builder $builder
         * @return string[]
         * Returns all invalid tags
         */
        public static function filterByTags(array &$tags, Builder $builder): array
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
                    $invalidTags[] = $tag;
                } else if (!$areInvalid) {
                    $translatedTags[] = $translatedId;
                }
            }
            if (!$areInvalid) {
                $taskIds = DB::table(TagTask::getTableName())
                    ->select([TagTaskConstants::COL_TASK_ID])
                    ->whereIn(TagTaskConstants::COL_TAG_ID, $translatedTags)
                    ->get()->toArray();
                $builder->whereIn(Task::getPrimaryKeyName(), $taskIds);
                return [];
            }
            return $translatedTags;
        }



        public static function filterByDifficultyRange(int $min, int $max, Builder $builder): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskDifficulty::class);
            if (!$RangeError) {
                $builder->whereBetween(TaskConstants::COL_DIFFICULTY, [$min, $max]);
            }
            return $RangeError;
        }

        public static function filterByClassRange(int $min, int $max, Builder $builder): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskClass::class);
            if (!$RangeError) {
                $builder->where(TaskConstants::COL_MIN_CLASS, '>=', $min);
                $builder->where(TaskConstants::COL_MAX_CLASS, '<=', $max);
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
            $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                $builder->whereBetween(
                    TaskConstants::COL_CREATED_AT,
                    [$minTimestamp, $maxTimestamp]
                );
                $rangeOrError = null;
            }
            return $rangeOrError;
        }
    }
}
