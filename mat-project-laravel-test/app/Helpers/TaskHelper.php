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
    use App\Dtos\Defs\Types\TaskInfo\AuthorInfo;
    use App\Dtos\Defs\Types\TaskInfo\TaskDetailInfo;
    use App\Dtos\Defs\Types\TaskInfo\TaskDetailInfoAuthor;
    use App\Dtos\Errors\ErrorResponse;
    use App\Dtos\InternalTypes\TaskSaveContent;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\ConversionException;
    use App\Exceptions\InternalException;
    use App\Helpers\BareModels\BareTaskWAuthorName;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\DBJsonHelper;
    use App\Models\Group;
    use App\Models\Resource;
    use App\Models\SavedTask;
    use App\Models\Tag;
    use App\Models\TagTask;
    use App\Models\TaskInfo;
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
            $groups = DB::table(Group::getTableName())
                ->select([$groupIdName, Group::START, Group::LENGTH])
                ->where(Group::TASK_ID, '=', $taskId)
                ->orderBy(Group::START, direction: 'asc')
                ->orderBy(Group::LENGTH, direction: 'desc')
                ->get();

            $resources = DB::table(Resource::getTableName())
                ->select([Resource::GROUP_ID, Resource::CONTENT])
                ->whereIn(Resource::GROUP_ID, $groups->keys())
                ->get();
            /**
             * @var array<mixed,string[]> $resourcesByGroupId
             */
            $resourcesByGroupId = [];
            while (($resource = $resources->pop()) !== null) {
                /**
                 * @var mixed $groupId
                 */
                $groupId = DBHelper::access($resource, Resource::GROUP_ID);
                /**
                 * @var string $content
                 */
                $content = DBHelper::access($resource, Resource::CONTENT);
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
                if ($exI === DBHelper::access($nextGroup, Group::START)) {
                    $groupId = DBHelper::access($nextGroup, $groupIdName);
                    $groupDto = $groupToDto($resourcesByGroupId[$groupId] ?? []);
                    unset($resourcesByGroupId[$groupId]);

                    $dest[] = $groupDto;
                    $stack[] = [$exerciseEnd, &$dest];
                    $dest = &$groupDto->entries;
                    $exerciseEnd = $exI + DBHelper::access($nextGroup, Group::LENGTH);
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
                    ->select([SavedTask::DATA, SavedTask::TASK_VERSION])
                    ->where(SavedTask::USER_ID, '=', $user->id)
                    ->where(SavedTask::TASK_ID, '=', $taskId);
                if ($localySavedTaskUtcTimestamp) {
                    $builder = $builder
                        ->where(SavedTask::UPDATED_AT, '>', $localySavedTaskUtcTimestamp, boolean: 'or')
                        ->where(SavedTask::CREATED_AT, '>', $localySavedTaskUtcTimestamp);
                }
                $savedTask = $builder->first();
                if ($savedTask) {
                    $savedTaskData = $savedTask[SavedTask::DATA];
                    $taskVersion = $savedTaskData[SavedTask::TASK_VERSION];
                    $decodedSavedData = TaskSaveContent::import(
                        (object)[
                            TaskSaveContent::EXERCISES =>
                            DBJsonHelper::decode(
                                $savedTaskData,
                                table: $savedTaskTable,
                                column: SavedTask::DATA,
                                id: [SavedTask::USER_ID => $user->id, SavedTask::TASK_ID => $taskId]
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
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTask::TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTask::TASK_ID),
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: Tag::NAME,
                        as: 'name'
                    )
                ])
                ->join(
                    $tagTable,
                    DBHelper::colExpression(
                        table: $tagTaskTable,
                        column: TagTask::TAG_ID
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
                $taskId = $tag[TagTask::TASK_ID];
                $tagsByTaskId[$taskId] ??= [];

                /**
                 * @var array{int,string}
                 */
                $tagData = [$tag[TagTask::TAG_ID] + 0, $tag['name'] . ''];
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
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTask::TAG_ID),
                    DBHelper::colFromTableAsCol($tagTaskTable, TagTask::TASK_ID),
                    DBHelper::colExpression(
                        table: $tagTable,
                        column: Tag::NAME,
                        as: 'name'
                    )
                ])
                ->join(
                    $tagTable,
                    DBHelper::colExpression(
                        table: $tagTaskTable,
                        column: TagTask::TAG_ID
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
                        column: TagTask::TASK_ID
                    ),
                    '=',
                    $taskId
                )
                ->get();

            $access = null;
            return $tags->mapWithKeys(function ($tag, $key) use ($access) {
                $access ??= Utils::getAccessor($tag);
                return [$access($tag, TagTask::TAG_ID) + 0 => $access($tag, 'name') . ''];
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
                    ->select([TagTask::TASK_ID])
                    ->whereIn(TagTask::TAG_ID, $translatedTags)
                    ->get()->toArray();
                $builder->whereIn(TaskInfo::getPrimaryKeyName(), $taskIds);
                return [];
            }
            return $translatedTags;
        }



        public static function filterByDifficultyRange(int $min, int $max, Builder $builder): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskDifficulty::class);
            if (!$RangeError) {
                $builder->whereBetween(TaskInfo::DIFFICULTY, [$min, $max]);
            }
            return $RangeError;
        }

        public static function filterByClassRange(int $min, int $max, Builder $builder): RangeError|null
        {
            $RangeError = DtoHelper::validateEnumRange($min, $max, TaskClass::class);
            if (!$RangeError) {
                $builder->where(TaskInfo::MIN_CLASS, '>=', $min);
                $builder->where(TaskInfo::MAX_CLASS, '<=', $max);
            }
            return $RangeError;
        }

        public static function filterByModificationTimestamp(TimestampRange $range, Builder $builder): RangeError|null
        {
            $rangeOrError = DtoHelper::validateTimestampRange($range->min, $range->max);
            if (is_array($rangeOrError)) {
                [$minTimestamp, $maxTimestamp] = $rangeOrError;
                $builder->whereBetween(
                    DB::raw('COALESCE(' . TaskInfo::UPDATED_AT . ',' . TaskInfo::CREATED_AT . ')'),
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
                    TaskInfo::CREATED_AT,
                    [$minTimestamp, $maxTimestamp]
                );
                $rangeOrError = null;
            }
            return $rangeOrError;
        }
    }
}
