<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Types\Errors\EnumArrayError as ErrorsEnumArrayError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Defs\Types\MyTask\MyTaskDetailInfo;
use App\Dtos\Defs\Types\MyTask\MyTaskPreviewInfo;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
use App\Dtos\Defs\Types\Review\ExercisePoints;
use App\Dtos\Defs\Types\Review\ExerciseReview;
use App\Dtos\Defs\Types\Review\ReviewExerciseInstructions;
use App\Dtos\Defs\Types\Task\AuthorInfo;
use App\Dtos\Defs\Types\Task\TaskDetailInfo;
use App\Dtos\Defs\Types\Task\TaskDetailInfoAuthor;
use App\Dtos\Defs\Types\Task\TaskPreviewInfo;
use App\Dtos\Defs\Types\Task\TaskPreviewInfoAuthor;
use App\Dtos\Errors\ErrorResponse;
use App\Dtos\InternalTypes\TaskReviewExercisesContent;
use App\Dtos\Task as TaskDto;
use App\Dtos\Task\Create;
use App\Dtos\Task\Evaluate;
use App\Dtos\Task\Evaluate\Errors\TaskChangedTaskEvaluateError;
use App\Dtos\Task\List;
use App\Dtos\Task\List\Error\EnumArrayError;
use App\Dtos\Task\List\Error\GeneralErrorDetails;
use App\Dtos\Task\List\OrderByItems as ListOrderByItems;
use App\Dtos\Task\List\Request\OrderByItems;
use App\Dtos\Task\MyList;
use App\Dtos\Task\MyList\OrderByItems as MyListOrderByItems;
use App\Dtos\Task\Review;
use App\Dtos\Task\Take;
use App\Dtos\Task\Take\DefsExerciseInstructions;
use App\Dtos\Task\Take\NewerServerSavedTaskInfo;
use App\Dtos\Task\Take\OlderServerSavedTaskInfo;
use App\Dtos\Task\Take\SavedTaskInfo;
use App\Dtos\Task\Take\SavedTaskValues;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\InternalException;
use App\Helpers\BareModels\BareTask;
use App\Helpers\BareModels\BareTaskWAuthorName;
use App\Helpers\CreateTask\ParseEntry;
use App\Helpers\Database\DBHelper;
use App\Helpers\Database\UserHelper;
use App\Helpers\ExerciseHelper;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TaskHelper;
use App\ModelConstants\SavedTaskConstants;
use App\ModelConstants\TaskConstants;
use App\ModelConstants\TaskInfoConstants;
use App\ModelConstants\TaskReviewConstants;
use App\ModelConstants\TaskReviewTemplateConstants;
use App\Models\SavedTask;
use App\Models\Task;
use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use App\Types\EvaluateExercise;
use App\Types\TakeExercise;
use App\Utils\DebugUtils;
use App\Utils\DtoUtils;
use App\Utils\TimeStampUtils;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Swaggest\JsonSchema\InvalidValue;

class TaskController extends Controller
{

    public static function construct(): static
    {
        return new static();
    }


    /**
     * @throws ApplicationException
     * @throws AppModelNotFoundException
     */
    public function take(HttpRequest $request, int $id): Take\Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Take\Request::class, $request);
        $taskId = $id;
        $responseTask = Take\Task::create();
        $task = BareTaskWAuthorName::tryFetchById($taskId, publicOnly: true)
            ?? throw new AppModelNotFoundException('Task', ['id' => $taskId]);

        $responseTask->setTaskDetail(TaskHelper::getInfo($task))
            ->setDisplay(TaskDisplay::translate($task->display));

        DebugUtils::log("timestamp", $requestData->localySavedTask?->timestamp);
        // dump($requestData->localySavedTask?->timestamp);
        $localySavedTaskTimeStamp = $requestData->localySavedTask ?
            TimeStampUtils::tryParseIsoTimestampToUtc($requestData->localySavedTask->timestamp)
            : null;

        DebugUtils::log("localySavedTaskTimeStamp", $localySavedTaskTimeStamp);
        $saveTask = TaskHelper::getSavedTask(
            taskId: $taskId,
            localySavedTaskUtcTimestamp: $localySavedTaskTimeStamp
        );
        $useSavedTask = $saveTask && $task->version === $saveTask->taskVersion;
        $savedValuesInfo = null;
        if($saveTask){
            $savedValuesInfo = NewerServerSavedTaskInfo::create();
            if(!$useSavedTask){
                $savedValuesInfo->setPrevSavedValues(
                    SavedTaskValues::create()
                    ->setExercises($saveTask->content->exercises)
                );
            }
        }
        else{
            $savedValuesInfo = OlderServerSavedTaskInfo::create();
        }
        $responseTask->setSavedValuesInfo($savedValuesInfo);
        $exercises = ExerciseHelper::takeTaskInfo(
            taskInfoId: $task->taskInfoId,
            savedTask: $useSavedTask ? $saveTask : null
        );
        $responseTask->entries = [];
        $taskEntries = &$responseTask->entries;
        TaskHelper::getTaskEntries(
            taskInfoId: $task->taskInfoId,
            exercises: $exercises,
            exerciseToDto: function (TakeExercise $exercise) {
                $exerciseDto =  Take\DefsExercise::create()
                    ->setInstructions(DefsExerciseInstructions::create()
                        ->setContent($exercise->instructions));
                $exercise->impl->setAsContentTo($exerciseDto);
                return $exerciseDto;
            },
            groupToDto: function (array $resources) {
                $groupDto = Take\DefsGroup::create()
                    ->setResources(array_map(fn (string $resource) =>
                    Take\DefsGroupResourcesItems::create()
                        ->setContent($resource), $resources));
                return $groupDto;
            },
            entries: $taskEntries
        );
        return Take\Response::create()
            ->setTask($responseTask);
    }

    /**
     * @throws ApplicationException
     * @throws AuthenticationException
     * @throws InvalidValue
     */
    public function save(HttpRequest $request, int $id): Response
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(TaskDto\Save\Request::class, $request);
        $success = DB::table(SavedTask::getTableName())
            ->updateOrInsert(
                attributes: [
                    SavedTaskConstants::COL_TASK_ID => $id,
                    SavedTaskConstants::COL_USER_ID => $userId
                ],
                values: [
                    SavedTaskConstants::COL_DATA => TaskDto\Save\Request::export($requestData->exercises)
                ]
            );
        if (!$success) {
            throw new InternalException(
                "Could not save task values!",
                context: ['taskId' => $id]
            );
        }
        return response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws ApplicationException
     */
    public function store(HttpRequest $request): Create\Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Create\Request::class, $request);
        $parseEnty = new ParseEntry();
        $taskRes = $parseEnty->parse([$requestData->task->source]);
        $requestTask = &$requestData->task;
        $task = &$taskRes->task;
        if (!$task) {
            throw new InternalException(
                message: "Task should not be null, because it should be created while parsing task source.",
                context: ['taskRes' => $taskRes, 'task' => $task]
            );
        }
        $task->tagIds = array_map(
            fn ($tagId) => RequestHelper::translateId($tagId),
            $requestTask->tags
        );
        $task->difficulty = TaskDifficulty::fromThrow($requestTask->difficulty);
        $task->isPublic = $requestTask->isPublic;
        $task->minClass = TaskClass::fromThrow($requestTask->classRange->min);
        $task->maxClass = TaskClass::fromThrow($requestTask->classRange->max);

        $taskId = $taskRes->insert();
        return Create\Response::create()
            ->setTaskId($taskId);
    }

    public function update(HttpRequest $request, int $id): Response
    {
        $requestData = RequestHelper::getDtoFromRequest(TaskDto\Update\Request::class, $request);
        $parseEntry = new ParseEntry();
        $taskRes = $parseEntry->parse([$requestData->task->source]);
        $requestTask = &$requestData->task;
        $task = &$taskRes->task;
        if (!$task) {
            throw new InternalException(
                message: "Task should not be null, because it should be created while parsing task source.",
                context: ['taskRes' => $taskRes, 'task' => $task]
            );
        }
        $task->tagIds = array_map(
            fn ($tagId) => RequestHelper::translateId($tagId),
            $requestTask->tags
        );
        $task->difficulty = TaskDifficulty::fromThrow($requestTask->difficulty);
        $task->isPublic = $requestTask->isPublic;
        $task->minClass = TaskClass::fromThrow($requestTask->classRange->min);
        $task->maxClass = TaskClass::fromThrow($requestTask->classRange->max);

        $taskRes->update($id);
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function evaluate(HttpRequest $request, int $id): TaskDto\Review\Get\Response
    {
        $userId = UserHelper::tryGetUserId();
        $requestData = RequestHelper::getDtoFromRequest(Evaluate\Request::class, $request);
        $responseTask = Review\Get\Task::create();
        
       /**
         * @param ExerciseReview[]|null $evaluatedExercises
         * @param App\Dtos\Task\Review\Get\Task &$responseTask
         * @return BareTaskWAuthorName
         * Fetches the task and locks it if userId is not null
         * Evaluates task and sets response to responseTask
         * Adds evaluated exercises to evalutedExercises if evalutedExercises are not null
         */
    $do = function(array|null &$evaluatedExercises,&$responseTask) use($id, $userId,$requestData){
        $task = BareTaskWAuthorName::tryFetchById($id, publicOnly: true,sharedLock:$userId !== null)
            ?? throw new AppModelNotFoundException('Task', ['id' => $id]);
        if ($task->version !== $requestData->version) {
            throw new ApplicationException(
                userStatus: Response::HTTP_CONFLICT,
                userResponse: ErrorResponse::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage("Task changed.")
                            ->setDescription("Task was updated, so filled data do not longer represents valid data for this task.")
                    )
                    ->setDetails(
                        TaskChangedTaskEvaluateError::create()
                    )
            );
        }

        $responseTask->setDisplay($task->display);

        $exercises = ExerciseHelper::evaluateTaskInfo(
            taskInfoId: $task->taskInfoId
        );
        $taskPoints = 0;
        $taskmax = 0;
        $taskEntries = &$responseTask->setEntries([])->entries;
        TaskHelper::getTaskEntries(
            taskInfoId: $task->taskInfoId,
            exercises: $exercises,
            exerciseToDto: function (EvaluateExercise $exercise) use ($requestData, &$taskPoints, &$taskmax, &$evaluatedExercises) {
                $exerciseDto = ExerciseReview::create()
                    ->setInstructions(ReviewExerciseInstructions::create()
                        ->setContent($exercise->instructions));

                $exerciseValue = array_shift($requestData->exercises);
                $exercise->impl->evaluateAndSetAsContentTo($exerciseValue, $exerciseDto);

                $exerciseDto->points->has = ($exerciseDto->points->has * $exercise->weight) / $exerciseDto->points->max;
                $exerciseDto->points->max = $exercise->weight;

                $taskPoints += $exerciseDto->points->has;
                $taskmax += $exerciseDto->points->max;
                if($evaluatedExercises){
                $evaluatedExercises[] = $exerciseDto;
                }
                return $exerciseDto;
            },
            groupToDto: function (array $resources) {
                $groupDto = Review\Get\DefsGroup::create()
                    ->setResources(
                        array_map(
                            fn (string $resource) =>
                            Review\Get\DefsGroupResourcesItems::create()
                                ->setContent($resource),
                            $resources
                        )
                    );
                return $groupDto;
            },
            entries: $taskEntries
        );

        $responseTask->setPoints(
            ExercisePoints::create()
                ->setHas($taskPoints)
                ->setMax($taskmax)
        );
        return $task;
    };

    if($userId === null){
        $evaluatedExercises = null;
       $do($evaluatedExercises,$responseTask);
    }
    else{
        DB::transaction(function()use($do,$userId,$responseTask){
            /**
             * @var ExerciseReview[] $evaluatedExercises
             */
            $evaluatedExercises = [];
            $task = $do($evaluatedExercises,$responseTask);
            $templateId = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->select([TaskReviewTemplateConstants::COL_ID])
                ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $task->taskInfoId)
                ->sharedLock()
                ->value(TaskReviewTemplateConstants::COL_ID);

            if ($templateId === null) {
                $templateId = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                    ->insertGetId([
                        TaskReviewTemplateConstants::COL_TASK_ID => $task->id,
                        TaskReviewTemplateConstants::COL_TASK_INFO_ID => $task->taskInfoId
                    ]);
            }

            $exercises = TaskReviewExercisesContent::create()
            ->setContent($evaluatedExercises);
            $taskReviewData = [
                TaskReviewConstants::COL_USER_ID => $userId,
                TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID => $templateId,
                TaskReviewConstants::COL_MAX_POINTS => $responseTask->points->max,
                TaskReviewConstants::COL_SCORE => $responseTask->points->has / $responseTask->points->max,
                TaskReviewConstants::COL_EXERCISES => DtoUtils::dtoToJson(
                    dto:$exercises,
                    field:TaskReviewExercisesContent::CONTENT
                    )
            ];

                $inserted = DB::table(TaskReviewConstants::TABLE_NAME)
                    ->insert($taskReviewData);
                    if(!$inserted){
                        throw new InternalException(
                            message:"Could not insert task review.",
                            context:[
                                'taskReviewData' => $taskReviewData
                            ]
                            );
                    }
        });
    }
        return Review\Get\Response::create()
            ->setTask($responseTask);
    }

    public function list(HttpRequest $request): TaskDto\List\Response
    {
        $requestData = RequestHelper::getDtoFromRequest(List\Request::class, $request);

        $bareTasks = BareTaskWAuthorName::tryFetch(function (Builder $builder) use ($requestData) {
            /**
             * @var List\Errors\FilterErrorDetailsErrorData|null $error
             */
            $filterErrorData = null;

            $filters = $requestData->filters;
            if ($filters->tags) {
                $invalidTags = TaskHelper::filterTaskByTags($filters->tags, $builder);
                if ($invalidTags) {
                    ($filterErrorData ??= List\Errors\FilterErrorDetailsErrorData::create())
                        ->setTags(
                            ErrorsEnumArrayError::create()
                                ->setMessage("Invalid ids specified.")
                        );
                }
            }

            if ($filters->name) {
                $builder->whereRaw(
                    DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_NAME)
                        . " LIKE %?%",
                    [$filters->name]
                );
            }

            if ($filters->difficultyRange) {
                $RangeError = TaskHelper::filterTaskInfoByDifficultyRange(
                    min: $filters->difficultyRange->min,
                    max: $filters->difficultyRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= List\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filters->classRange) {
                $RangeError = TaskHelper::filterTaskInfoByClassRange(
                    min: $filters->classRange->min,
                    max: $filters->classRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= List\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filterErrorData) {
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
                    ErrorResponse::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("Bad request.")
                                ->setDescription("Please correct request fields.")
                        )
                        ->setDetails(
                            List\Errors\FilterErrorDetails::create()
                                ->setErrorData($filterErrorData)
                        )
                );
            }

            $transformOrderBy = function (array $orderBy) {
                /**
                 * @var OrderByItems[] $orderBy
                 */
                foreach ($orderBy as $filterAndOrder) {
                    yield $filterAndOrder->filterName =>
                        $filterAndOrder->type === ListOrderByItems::DESC ? 'DESC' : 'ASC';
                }
            };

            TaskHelper::distinctOrderBy(
                $transformOrderBy($requestData->orderBy),
                function (string $filterName, $direction) use ($builder) {
                    if ($filterName === ListOrderByItems::CLASS_RANGE) {
                        $builder->orderBy(
                            DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MIN_CLASS),
                            $direction
                        );
                        $builder->orderBy(
                            DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MAX_CLASS),
                            $direction
                        );
                    } else {
                        if ($filterName === ListOrderByItems::DIFFICULTY) {
                            $column = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_DIFFICULTY);
                        } else if ($filterName === ListOrderByItems::NAME) {
                            $column = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_NAME);
                        } else {
                            return false;
                        }
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        });

        $taskInfoIds = [];
        foreach($bareTasks as $bareTask){
           $taskInfoIds[$bareTask->taskInfoId] = true;
        }
        $taskInfoIds = array_keys($taskInfoIds);
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        unset($taskInfoIds);

        $tasks = $bareTasks->map(function (BareTaskWAuthorName $task, $key) use (&$tagsByTaskInfoId): TaskPreviewInfo {
            $info = TaskPreviewInfo::create()
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setAuthor(
                    AuthorInfo::create()
                        ->setId($task->userId)
                        ->setName($task->authorName)
                )
                ->setDifficulty(
                    DtoUtils::createOrderedEnumDto($task->difficulty)
                )
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setmin(DtoUtils::createOrderedEnumDto($task->minClass))
                        ->setmax(DtoUtils::createOrderedEnumDto($task->maxClass))
                )
                ->setTags(
                    array_map(function (array $tag) {
                        /**
                         * @var array{int,string} $tag
                         */
                        return ResponseEnumElement::create()
                            ->setId(ResponseHelper::translateIdForUser($tag[0]))
                            ->setName($tag[1]);
                    }, $tagsByTaskInfoId[$task->taskInfoId])
                );
            if ($task->taskReviewId !== null) {
                $info->setTaskReviewId(
                    ResponseHelper::translateIdForUser($task->taskReviewId)
                );
            }
            return $info;
        })->all();

        return List\Response::create()
            ->setTasks($tasks);
    }

    public function detail(HttpRequest $request, int $taskId): TaskDto\Detail\Response
    {
        $task = BareTaskWAuthorName::tryFetchById($taskId, publicOnly: true);
        if (!$task) {
            throw new AppModelNotFoundException(Task::class, withProperties: ['id' => $taskId]);
        }
        $tags = TaskHelper::getTaskInfoTags($task->taskInfoId);

        $taskDetailInfo = TaskDetailInfo::create()
            ->setId($task->id)
            ->setName($task->name)
            ->setVersion($task->version . '')
            ->setAuthor(
                AuthorInfo::create()
                    ->setId($task->userId)
                    ->setName($task->authorName)
            )
            ->setClassRange(
                ResponseOrderedEnumRange::create()
                    ->setMin($task->minClass)
                    ->setMax($task->maxClass)
            )
            ->setDifficulty(
                ResponseOrderedEnumElement::create()
                    ->setOrderedId($task->difficulty->value)
                    ->setName($task->difficulty->name)
            )
            ->setTags($tags->map(
                fn (string $tag, int $tagId) =>
                ResponseEnumElement::create()
                    ->setId(ResponseHelper::translateIdForUser($tagId))
                    ->setName($tag)
            ));

        if ($task->taskReviewId !== null) {
            $taskDetailInfo->setTaskReviewId(
                ResponseHelper::translateIdForUser($task->taskReviewId)
            );
        }

        return TaskDto\Detail\Response::create()
            ->setTask($taskDetailInfo);
    }

    public function delete(HttpRequest $request, int $taskId): Response
    {
        DB::transaction(function () use ($taskId) {
            $taskInfoId = DB::table(TaskConstants::TABLE_NAME)
            ->select([TaskConstants::COL_TASK_INFO_ID])
            ->where(TaskConstants::COL_ID, '=', $taskId)
            ->value(TaskConstants::COL_TASK_INFO_ID);
            if($taskInfoId === null){
                throw new AppModelNotFoundException("Task", ['id' => $taskId]);
            }

            $deleted = DB::table(TaskConstants::TABLE_NAME)
                ->delete($taskId);
            if ($deleted === 0) {
               // we have there some concurrent query, so we let it to do the rest
               return;
            }

            $taskReviewTemplateExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=',$taskInfoId)
                ->exists();


            if (!$taskReviewTemplateExists) {
                DB::table(TaskInfoConstants::TABLE_NAME)
                    ->delete($taskInfoId);
                    // We do not need to delete groups, tags, or exercises,
                    // because all of them should be deleted by cascade
            }
            else{
                TaskHelper::deleteActualExercisesByTaskInfo($taskInfoId);
            }
        });
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function myDetail(HttpRequest $request, int $taskId): TaskDto\MyDetail\Response
    {
        $task = BareTask::tryFetchById($taskId);
        if (!$task) {
            throw new AppModelNotFoundException(Task::class, withProperties: ['id' => $taskId]);
        }
        $tags = TaskHelper::getTaskInfoTags($task->taskInfoId);

        return TaskDto\MyDetail\Response::create()
            ->setTask(
                MyTaskDetailInfo::create()
                    ->setId($task->id)
                    ->setName($task->name)
                    ->setVersion($task->version . '')
                    ->setCreationTimestamp(TimeStampUtils::timestampToString($task->createdAt))
                    ->setModificationTimestamp(TimeStampUtils::timestampToString($task->updatedAt))
                    ->setClassRange(
                        ResponseOrderedEnumRange::create()
                            ->setMin($task->minClass)
                            ->setMax($task->maxClass)
                    )
                    ->setDifficulty(
                        ResponseOrderedEnumElement::create()
                            ->setOrderedId($task->difficulty->value)
                            ->setName($task->difficulty->name)
                    )
                    ->setTags($tags->map(
                        fn (string $tag, int $tagId) =>
                        ResponseEnumElement::create()
                            ->setId(ResponseHelper::translateIdForUser($tagId))
                            ->setName($tag)
                    ))
            );
    }

    public function myList(HttpRequest $request): TaskDto\MyList\Response
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(MyList\Request::class, $request);

        $bareTasks = BareTask::tryFetch(function (Builder $builder) use ($requestData,$userId) {
            /**
             * @var MyList\Errors\FilterErrorDetailsErrorData|null $error
             */
            $filterErrorData = null;
            $builder->where(TaskConstants::COL_USER_ID, $userId);

            $filters = $requestData->filters;
            if ($filters->tags) {
                $invalidTags = TaskHelper::filterTaskByTags($filters->tags, $builder);
                if ($invalidTags) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setTags(
                            ErrorsEnumArrayError::create()
                                ->setMessage("Invalid ids specified.")
                        );
                }
            }

            if ($filters->name) {
                $builder->whereRaw(
                    DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_NAME)
                        . " LIKE %?%",
                    [$filters->name]
                );
            }

            if ($filters->difficultyRange) {
                $RangeError = TaskHelper::filterTaskInfoByDifficultyRange(
                    min: $filters->difficultyRange->min,
                    max: $filters->difficultyRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filters->classRange) {
                $RangeError = TaskHelper::filterTaskInfoByClassRange(
                    min: $filters->classRange->min,
                    max: $filters->classRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if (($modificationRange = $filters->modificationTimestampRange)) {
                $rangeError = TaskHelper::filterByModificationTimestamp($modificationRange, $builder);
                if ($rangeError) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setModificationTimestampRange($rangeError);
                }
            }
            if (($creationRange = $filters->creationTimestampRange)) {
                $rangeError = TaskHelper::filterByCreationTimestamp($creationRange, $builder);
                if ($rangeError) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setCreationTimestampRange($rangeError);
                }
            }


            if ($filterErrorData) {
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
                    ErrorResponse::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("Bad request.")
                                ->setDescription("Please correct request fields.")
                        )
                        ->setDetails(
                            MyList\Errors\FilterErrorDetails::create()
                                ->setErrorData($filterErrorData)
                        )
                );
            }

            $transformOrderBy = function (array $orderBy) {
                /**
                 * @var MyListOrderByItems[] $orderBy
                 */
                foreach ($orderBy as $filterAndOrder) {
                    yield $filterAndOrder->filterName =>
                        $filterAndOrder->type === MyListOrderByItems::DESC ? 'DESC' : 'ASC';
                }
            };

            TaskHelper::distinctOrderBy(
                $transformOrderBy($requestData->orderBy),
                function (string $filterName, $direction) use ($builder) {
                    if ($filterName === MyListOrderByItems::CLASS_RANGE) {
                        $builder->orderBy(
                            DBHelper::tableCol(
                                TaskInfoConstants::TABLE_NAME,
                                TaskInfoConstants::COL_MIN_CLASS
                            ),
                            $direction
                        );
                        $builder->orderBy(
                            DBHelper::tableCol(
                                TaskInfoConstants::TABLE_NAME,
                                TaskInfoConstants::COL_MAX_CLASS
                            ),
                            $direction
                        );
                    } else {
                        if ($filterName === MyListOrderByItems::DIFFICULTY) {
                            $column = DBHelper::tableCol(
                                TaskInfoConstants::TABLE_NAME,
                                TaskInfoConstants::COL_DIFFICULTY
                            );
                        } else if ($filterName === MyListOrderByItems::NAME) {
                            $column = DBHelper::tableCol(
                                TaskInfoConstants::TABLE_NAME,
                                TaskInfoConstants::COL_NAME
                            );
                        } else {
                            return false;
                        }
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        });
        $taskInfoIds = $bareTasks->map(fn(BareTask $bareTask)=>$bareTask->taskInfoId)
        ->all();
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        unset($taskInfoIds);

        $tasks = $bareTasks->map(function (BareTask $task, $key) use (&$tagsByTaskInfoId): MyTaskPreviewInfo {
            $info = MyTaskPreviewInfo::create()
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setCreationTimestamp(TimeStampUtils::timestampToString($task->createdAt));
            if (($updatedAt = $task->updatedAt)) {
                $info->setModificationTimestamp(TimeStampUtils::timestampToString($updatedAt));
            }
            $info->setDifficulty(
                DtoUtils::createOrderedEnumDto($task->difficulty)
            )
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setmin(DtoUtils::createOrderedEnumDto($task->minClass))
                        ->setmax(DtoUtils::createOrderedEnumDto($task->maxClass))
                )
                ->setTags(
                    array_map(function (array $tag) {
                        /**
                         * @var array{int,string} $tag
                         */
                        return ResponseEnumElement::create()
                            ->setId(ResponseHelper::translateIdForUser($tag[0]))
                            ->setName($tag[1]);
                    }, $tagsByTaskInfoId[$task->taskInfoId])
                );
            return $info;
        })->all();

        return MyList\Response::create()
            ->setTasks($tasks);
    }
}
