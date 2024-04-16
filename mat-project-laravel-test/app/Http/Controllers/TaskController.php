<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Endpoints\Task\Create\TaskCreateResponse;
use App\Dtos\Defs\Endpoints\Task\Take\TakeTaskResponse;
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
use App\Dtos\Defs\Types\Task\TaskPreviewInfo;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Dtos\InternalTypes\TaskReviewExercisesContent;
use App\Dtos\Defs\Endpoints\Task as TaskDto;
use App\Dtos\Defs\Endpoints\Task\Evaluate\Errors\TaskChangedTaskEvaluateError;
use App\Dtos\Defs\Endpoints\Task\Create;
use App\Dtos\Defs\Endpoints\Task\Create\CreateRequestTask;
use App\Dtos\Defs\Endpoints\Task\Create\Errors\TaskCreateErrorDetails;
use App\Dtos\Defs\Endpoints\Task\Create\Errors\TaskCreateErrorDetailsErrorData;
use App\Dtos\Defs\Endpoints\Task\Save;
use App\Dtos\Defs\Endpoints\Task\Update;
use App\Dtos\Defs\Endpoints\Task\Detail;
use App\Dtos\Defs\Endpoints\Task\Evaluate;
use App\Dtos\Defs\Endpoints\Task\Source;
use App\Dtos\Defs\Endpoints\Task\Evaluate\Errors\MismatchedExerciseValueEvaluateError;
use App\Dtos\Defs\Endpoints\Task\Evaluate\EvaluateResponseTask;
use App\Dtos\Defs\Endpoints\Task\List;
use App\Dtos\Defs\Endpoints\Task\List\ListResponseConfig;
use App\Dtos\Defs\Endpoints\Task\MyList;
use App\Dtos\Defs\Endpoints\Task\Review;
use App\Dtos\Defs\Endpoints\Task\Take;
use App\Dtos\Defs\Endpoints\Task\MyDetail;
use App\Dtos\Defs\Endpoints\Task\Take\DefsExerciseInstructions;
use App\Dtos\Defs\Endpoints\Task\Take\NewerServerSavedTaskInfo;
use App\Dtos\Defs\Endpoints\Task\Take\OlderServerSavedTaskInfo;
use App\Dtos\Defs\Endpoints\Task\Take\SavedTaskValues;
use App\Dtos\Defs\Endpoints\Task\Take\TakeResponseTaskTaskDetail;
use App\Dtos\Defs\Types\ListConfig;
use App\Dtos\Defs\Types\Task\TaskDetailInfoTaskReview;
use App\Dtos\Defs\Types\Task\TaskPreviewInfoTaskReview;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidArgumentException as ExceptionsInvalidArgumentException;
use App\Exceptions\InvalidEvaluateValueException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\BareModels\BareDetailTask;
use App\Helpers\BareModels\BareEvaluateTask;
use App\Helpers\BareModels\BareListTask;
use App\Helpers\BareModels\BareTakeTask;
use App\Helpers\BareModels\BareTask;
use App\Utils\DebugLogger;
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
use App\ModelConstants\TaskSourceConstants;
use App\TableSpecificData\TaskDisplay;
use App\Types\EvaluateExercise;
use App\Types\SimpleQueryWheresBuilder;
use App\Types\StopWatchTimer;
use App\Types\TakeExercise;
use App\Types\TaskResTask;
use App\Utils\DtoUtils;
use App\Utils\TimeStampUtils;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Swaggest\JsonSchema\InvalidValue;
use Throwable;

class TaskController extends Controller
{

    public function source(HttpRequest $request, int $id): Source\TaskSourceResponse
    {

        $source = DB::table(TaskConstants::TABLE_NAME)
            ->select([TaskConstants::COL_SOURCE])
            ->where(TaskConstants::COL_ID, '=', $id)
            ->value(TaskConstants::COL_SOURCE);
        if (!is_string($source)) {
            throw new AppModelNotFoundException("Task", ['id' => $id]);
        }
        return Source\TaskSourceResponse::create()
            ->setSource($source);
    }

    /**
     * @param HttpRequest $request
     * @param int $id
     * @return TakeTaskResponse
     * @throws AppModelNotFoundException
     * @throws ApplicationException
     * @throws ValidationException
     */
    public function take(HttpRequest $request, int $id): Take\TakeTaskResponse
    {
        $requestData = RequestHelper::getDtoFromRequest(Take\TakeTaskRequest::class, $request);
        $taskId = $id;
        $responseTask = Take\TakeResponseTask::create();
        $task = BareTakeTask::tryFetchPublic($taskId)
            ?? throw new AppModelNotFoundException('Task', ['id' => $taskId]);

        $detail = TakeResponseTaskTaskDetail::create()
            ->setId(ResponseHelper::translateIdForUser($task->id))
            ->setName($task->name)
            ->setDescription($task->description)
            ->setVersion(ResponseHelper::translateIdForUser($task->version));

        $responseTask->setTaskDetail($detail)
            ->setDisplay(match ($task->orientation) {
                TaskDisplay::HORIZONTAL => Take\TakeResponseTask::HORIZONTAL,
                TaskDisplay::VERTICAL => Take\TakeResponseTask::VERTICAL,
                default => throw new UnsupportedVariantException($task->orientation)
            });

        //DebugLogger::log("timestamp", $requestData->localySavedTask?->timestamp ?? null);
        // dump($requestData->localySavedTask?->timestamp);
        $localySavedTaskTimeStamp = $requestData->localySavedTask ?
            TimeStampUtils::tryParseIsoTimestampToUtc($requestData->localySavedTask->timestamp)
            : null;

        //DebugLogger::log("localySavedTaskTimeStamp", $localySavedTaskTimeStamp);
        $saveTask = TaskHelper::getSavedTask(
            taskId: $taskId,
            localySavedTaskUtcTimestamp: $localySavedTaskTimeStamp
        );
        $useSavedTask = $saveTask && $task->version === $saveTask->taskVersion;
        $savedValuesInfo = null;
        if ($saveTask) {
            $savedValuesInfo = NewerServerSavedTaskInfo::create();
            if (!$useSavedTask) {
                $savedValuesInfo->setPrevSavedValues(
                    SavedTaskValues::create()
                        ->setExercises($saveTask->content->exercises)
                );
            }
        } else {
            $savedValuesInfo = OlderServerSavedTaskInfo::create();
        }
        $responseTask->setSavedValuesInfo($savedValuesInfo);
        $exercises = ExerciseHelper::takeTaskSource(
            taskSourceId: $task->taskSourceId,
            savedTask: $useSavedTask ? $saveTask : null
        );
        $responseTask->entries = [];
        $taskEntries = &$responseTask->entries;
        TaskHelper::getTaskEntries(
            taskSourceId: $task->taskSourceId,
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
        //DebugLogger::debug("responseTaskTask: ",$responseTask);
        return Take\TakeTaskResponse::create()
            ->setTask($responseTask);
    }

    /**
     * @param HttpRequest $request
     * @param int $id
     * @return Response
     * @throws ApplicationException
     * @throws AuthenticationException
     * @throws InvalidValue
     * @throws ValidationException
     */
    public function save(HttpRequest $request, int $id): Response
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(Save\SaveTaskRequest::class, $request);
        $success = null;
        try{
        $success = DBHelper::insertOrUpdate(
            table:SavedTaskConstants::TABLE_NAME,
            attributes: [
                SavedTaskConstants::COL_TASK_ID => $id,
                SavedTaskConstants::COL_USER_ID => $userId
            ],
            values: [
                SavedTaskConstants::COL_DATA => DtoUtils::dtoToJson($requestData,$requestData::EXERCISES)
            ]);
        }
        catch(\Throwable $e){
            $success = false;
        }
        if (is_bool($success) && !$success) {
            throw new InternalException(
                "Could not save task values!",
                context: ['taskId' => $id]
            );
        }
        return response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @param HttpRequest $request
     * @return TaskCreateResponse
     * @throws ApplicationException
     * @throws ValidationException
     * @throws \Throwable
     */
    public function store(HttpRequest $request): Create\TaskCreateResponse
    {
        $requestData = RequestHelper::getDtoFromRequest(Create\TaskCreateRequest::class, $request);
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

        $task->name = $requestTask->name;
        $task->display = match ($requestTask->display) {
            CreateRequestTask::HORIZONTAL => TaskDisplay::HORIZONTAL,
            CreateRequestTask::VERTICAL => TaskDisplay::VERTICAL,
            default => throw new UnsupportedVariantException($task->display)
        };
        $task->isPublic = $requestTask->isPublic;

        /**
         * @var TaskCreateErrorDetailsErrorData|null $errorData
         */
        $errorData = null;
        if (($error = TaskHelper::setTagsToTaskResTask($requestTask->tags, $task))) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setTags($error);
        }
        if (($error = TaskHelper::setDifficultyToTaskResTask($requestTask->difficulty, $task))) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setDifficulty($error);
        }

        if (($error = TaskHelper::setClassRangeToTaskResTask($requestTask->classRange, $task))) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setClassRange($error);
        }

        if ($errorData) {
            throw new ApplicationException(
                Response::HTTP_BAD_REQUEST,
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage("Bad request.")
                            ->setDescription("Please correct request fields.")
                    )
                    ->setDetails(
                        TaskCreateErrorDetails::create()
                            ->setErrorData($errorData)
                    )
            );
        }

        $taskId = $taskRes->insert($requestData->task->source);
        return Create\TaskCreateResponse::create()
            ->setTaskId(ResponseHelper::translateIdForUser($taskId));
    }

    public function update(HttpRequest $request, int $id): Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Update\TaskUpdateRequest::class, $request);
        $requestTask = $requestData->task;
        /**
         * @var ?TaskResTask $task
         */
        $task = null;
        if (isset($requestData->task->source)) {
            $parseEntry = new ParseEntry();
            $taskRes = $parseEntry->parse([$requestData->task->source]);
            $task = &$taskRes->task;
            if (!$task) {
                throw new InternalException(
                    message: "Task should not be null, because it should be created while parsing task source.",
                    context: ['taskRes' => $taskRes, 'task' => $task]
                );
            }
        } else {
            $task = new TaskResTask();
        }

        if (isset($requestTask->name)) {
            $task->name = $requestTask->name;
        }
        if (isset($requestTask->display)) {
            $task->display = match ($requestTask->display) {
                CreateRequestTask::HORIZONTAL => TaskDisplay::HORIZONTAL,
                CreateRequestTask::VERTICAL => TaskDisplay::VERTICAL,
                default => throw new ExceptionsInvalidArgumentException(
                    argumentName: 'requestTask->display',
                    argumentValue: $requestTask->display
                )
            };
        }

        if (isset($requestTask->isPublic)) {
            $task->isPublic = $requestTask->isPublic;
        }

        /**
         * @var TaskCreateErrorDetailsErrorData|null $errorData
         */
        $errorData = null;
        if (
            isset($requestTask->tags)
            && ($error = TaskHelper::setTagsToTaskResTask($requestTask->tags, $task))
        ) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setTags($error);
        }
        if (
            isset($requestTask->difficulty)
            && ($error = TaskHelper::setDifficultyToTaskResTask($requestTask->difficulty, $task))
        ) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setDifficulty($error);
        }

        if (
            isset($requestTask->classRange)
            && ($error = TaskHelper::setClassRangeToTaskResTask($requestTask->classRange, $task))
        ) {
            ($errorData ??= TaskCreateErrorDetailsErrorData::create())
                ->setClassRange($error);
        }

        if ($errorData) {
            throw new ApplicationException(
                Response::HTTP_BAD_REQUEST,
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage("Bad request.")
                            ->setDescription("Please correct request fields.")
                    )
                    ->setDetails(
                        TaskCreateErrorDetails::create()
                            ->setErrorData($errorData)
                    )
            );
        }
        if (isset($requestTask->source)) {
            $taskRes->update($id, $requestData->task->source);
        } else {
            $taskInfoBindings = [];
            TaskHelper::addExistingTaskResTaskDataToTaskInfoBindings(
                taskInfoBindings: $taskInfoBindings,
                task: $task
            );
            StopWatchTimer::run("taskUpdate no source transaction",fn()=>
            DB::transaction(function () use ($id, $task, $taskInfoBindings) {
                // This probably should be on start of update, 
                // but locking for whole parsing + problems with double locking and nested transcations 
                // (TaskRes::update does transacion and locking) is not probably worth to do it.
                $taskInfoId = DB::table(TaskConstants::TABLE_NAME)
                    ->select([TaskConstants::COL_TASK_INFO_ID])
                    ->where(TaskConstants::COL_ID, '=', $id)
                    ->lockForUpdate()
                    ->value(TaskConstants::COL_TASK_INFO_ID)
                    ?? throw new AppModelNotFoundException("Task",['id' => $id]);

                $reviewTemplateExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                    ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                    ->exists();

                $taskBindings = [
                    TaskConstants::COL_VERSION => DB::raw(TaskConstants::COL_VERSION . " + 1")
                ];
                if (isset($task->name)) {
                    $taskBindings[TaskConstants::COL_NAME] = $task->name;
                }
                if(isset($task->isPublic)){
                    $taskBindings[TaskConstants::COL_IS_PUBLIC] = $task->isPublic;
                }
                if ($reviewTemplateExists) {
                    $newTaskInfoId = TaskHelper::insertNewTaskInfoGetId($taskInfoBindings,$taskInfoId);
                    if (!is_int($newTaskInfoId)) {
                        throw new InternalException(
                            message: "Could not insert task info using values from task info with id '$taskInfoId'.",
                            context: [
                                'taskInfoId' => $taskInfoId,
                                'taskInfoBindings' => $taskInfoBindings
                            ]
                        );
                    }
                    $taskBindings[TaskConstants::COL_TASK_INFO_ID] = $newTaskInfoId;
                } else {
                    DB::table(TaskInfoConstants::TABLE_NAME)
                        ->where(TaskInfoConstants::COL_ID, '=', $taskInfoId)
                        ->update($taskInfoBindings);
                }
                $currentTaskInfoId = $taskBindings[TaskConstants::COL_TASK_INFO_ID] ?? $taskInfoId;
                TaskHelper::insertOrReplaceTags(
                    taskInfoId:$currentTaskInfoId,
                    tagIds:$task->tagIds,
                    replace:$currentTaskInfoId === $taskInfoId
                );

                TaskHelper::insertOrUpdateTaskWUniqueName(
                    fn()=>DB::table(TaskConstants::TABLE_NAME)
                    ->where(TaskConstants::COL_ID, '=', $id)
                    ->update($taskBindings),
                    insert:false,
                    name:$taskBindings[TaskConstants::COL_NAME] ?? null
                );
            }));
        }

        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function evaluate(HttpRequest $request, int $id): Review\Get\ReviewTaskResponse
    {
        $userId = UserHelper::tryGetUserId();
        $requestData = RequestHelper::getDtoFromRequest(Evaluate\EvaluateTaskRequest::class, $request);
        $responseTask = EvaluateResponseTask::create();
        $evaluatedAt = Carbon::now();
        TimeStampUtils::timestampToUtc($evaluatedAt);

        /**
         * @param ExerciseReview[]|null $evaluatedExercises
         * @param EvaluateResponseTask &$responseTask
         * @return BareEvaluateTask
         * Fetches the task and locks it if userId is not null
         * Evaluates task and sets response to responseTask
         * Adds evaluated exercises to evaluatedExercises if evaluatedExercises are not null
         * @throws AppModelNotFoundException
         * @throws ApplicationException
         */
        $do = function (array|null &$evaluatedExercises, &$responseTask) use ($id, $evaluatedAt, $requestData) {
            $task = BareEvaluateTask::tryFetchPublic($id)
                ?? throw new AppModelNotFoundException('Task', ['id' => $id]);
            if ($task->version !== RequestHelper::translateId($requestData->version)) {
                throw new ApplicationException(
                    userStatus: Response::HTTP_CONFLICT,
                    userResponse: ApplicationErrorInformation::create()
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
            $responseTask->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setDescription($task->description)
                ->setEvaluationTimestamp(TimeStampUtils::timestampToString($evaluatedAt))
                ->setDisplay(match ($task->orientation) {
                    TaskDisplay::HORIZONTAL => EvaluateResponseTask::HORIZONTAL,
                    TaskDisplay::VERTICAL => EvaluateResponseTask::VERTICAL,
                    default => throw new UnsupportedVariantException($task->orientation)
                });

            $exercises = ExerciseHelper::evaluateTaskSource(
                taskSourceId: $task->taskSourceId
            );

            $taskPoints = 0;
            $taskmax = 0;
            $taskEntries = &$responseTask->setEntries([])->entries;
            TaskHelper::getTaskEntries(
                taskSourceId: $task->taskSourceId,
                exercises: $exercises,
                exerciseToDto: function (EvaluateExercise $exercise, int $i) use ($requestData, &$taskPoints, &$taskmax, &$evaluatedExercises) {
                    //DebugLogger::log("exerciseToDto - task evaluate");
                    $exerciseDto = ExerciseReview::create()
                        ->setInstructions(
                            ReviewExerciseInstructions::create()
                                ->setContent($exercise->instructions)
                        );

                    $exerciseValue = array_shift($requestData->exercises);
                    try {
                            $exercise->impl->evaluateAndSetAsContentTo($exerciseValue, $exerciseDto);
                    } catch (InvalidEvaluateValueException $e) {
                        throw new ApplicationException(
                            Response::HTTP_BAD_REQUEST,
                            ApplicationErrorInformation::create()
                                ->setUserInfo(
                                    UserSpecificPartOfAnError::create()
                                        ->setMessage("Mismatched value for $i. exercise.")
                                        ->setDescription("Expected valid value for '" . $exercise->type->translate() . "'.")
                                )
                                ->setDetails(
                                    MismatchedExerciseValueEvaluateError::create()
                                )
                        );
                    }

                    $exerciseDto->points->has = ($exerciseDto->points->has * $exercise->weight) / $exerciseDto->points->max;
                    $exerciseDto->points->max = $exercise->weight;

                    $taskPoints += $exerciseDto->points->has;
                    $taskmax += $exerciseDto->points->max;
                    if ($evaluatedExercises !== null) {
                        //DebugLogger::log("push evaluated exercise to evaluated exercises");
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

        if ($userId === null) {
            $evaluatedExercises = [];
            $do($evaluatedExercises, $responseTask);
        } else {
            DB::transaction(function () use ($do, $userId, $responseTask,$evaluatedAt) {
                /**
                 * @var ExerciseReview[] $evaluatedExercises
                 */
                $evaluatedExercises = [];
                $task = $do($evaluatedExercises, $responseTask);
                $templateId = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                    ->select([TaskReviewTemplateConstants::COL_ID])
                    ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $task->taskInfoId)
                    ->sharedLock()
                    ->value(TaskReviewTemplateConstants::COL_ID);

                if ($templateId === null) {
                    $templateId = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                        ->insertGetId([
                            TaskReviewTemplateConstants::COL_AUTHOR_ID => $task->authorId,
                            TaskReviewTemplateConstants::COL_AUTHOR_NAME => $task->authorName,
                            TaskReviewTemplateConstants::COL_TASK_NAME => $task->name,
                            TaskReviewTemplateConstants::COL_TASK_ID => $task->id,
                            TaskReviewTemplateConstants::COL_TASK_INFO_ID => $task->taskInfoId
                        ]);
                }

                $exercises = TaskReviewExercisesContent::create()
                    ->setContent($evaluatedExercises);
                $taskReviewData = [
                    TaskReviewConstants::COL_USER_ID => $userId,
                    TaskReviewConstants::COL_EVALUATED_AT => $evaluatedAt,
                    TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID => $templateId,
                    TaskReviewConstants::COL_MAX_POINTS => $responseTask->points->max,
                    TaskReviewConstants::COL_SCORE => $responseTask->points->has / $responseTask->points->max,
                    TaskReviewConstants::COL_EXERCISES => DtoUtils::dtoToJson(
                        dto: $exercises,
                        field: TaskReviewExercisesContent::CONTENT
                    )
                ];

                $inserted = DBHelper::insertOrUpdate(
                    table:TaskReviewConstants::TABLE_NAME,
                    attributes:[
                        TaskReviewConstants::COL_USER_ID => $userId,
                        TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID => $templateId
                    ],
                    values:$taskReviewData
                );
                if (is_bool($inserted) && !$inserted) {
                    throw new InternalException(
                        message: "Could not insert task review.",
                        context: [
                            'taskReviewData' => $taskReviewData
                        ]
                    );
                }
            });
        }
        return Review\Get\ReviewTaskResponse::create()
            ->setTask($responseTask);
    }

    public function list(HttpRequest $request): List\ListTasksResponse
    {
        $requestData = RequestHelper::getDtoFromRequest(List\ListTasksRequest::class, $request);
        $config = ListConfig::create();

        $bareTasks = BareListTask::tryFetchPublic(function (Builder $builder) use ($requestData, $config) {
            /**
             * @var List\Errors\FilterErrorDetailsErrorData|null $error
             */
            $filterErrorData = null;

            $filters = $requestData->filters;
            if ($filters) {
                if ($filters->tags) {
                    $invalidTags = TaskHelper::filterTaskByTags($filters->tags, $builder, hasAll: true);
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
                        DBHelper::tableCol(TaskConstants::TABLE_NAME, TaskConstants::COL_NAME)
                            . " LIKE ?",
                        ["%{$filters->name}%"]
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
            }
            if ($filterErrorData) {
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
                    ApplicationErrorInformation::create()
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
                 * @var List\ListRequestOrderByItems[] $orderBy
                 */
                foreach ($orderBy as $filterAndOrder) {
                    yield $filterAndOrder->filterName =>
                        $filterAndOrder->type === List\ListRequestOrderByItems::DESC ? 'DESC' : 'ASC';
                }
            };

            if ($requestData->orderBy) {
                TaskHelper::distinctOrderBy(
                    $transformOrderBy($requestData->orderBy),
                    function (string $filterName, $direction) use ($builder) {
                        if ($filterName === List\ListRequestOrderByItems::CLASS_RANGE) {
                            $builder->orderBy(
                                DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MIN_CLASS),
                                $direction
                            );
                            $builder->orderBy(
                                DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MAX_CLASS),
                                $direction
                            );
                        } else {
                            if ($filterName === List\ListRequestOrderByItems::DIFFICULTY) {
                                $column = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_DIFFICULTY);
                            } else if ($filterName === List\ListRequestOrderByItems::NAME) {
                                $column = DBHelper::tableCol(TaskConstants::TABLE_NAME, TaskConstants::COL_NAME);
                            } else {
                                return false;
                            }
                            $builder->orderBy($column, $direction);
                        }
                        return true;
                    }
                );
            }
            $paginator = $builder->orderBy(TaskConstants::COL_ID)
                ->cursorPaginate(
                    perPage: $requestData->options->limit,
                    cursor: $requestData->options->cursor
                );
            $nextCursor = $paginator->nextCursor();
            $prevCursor = $paginator->previousCursor();
            if ($nextCursor) {
                $config->setNextCursor($nextCursor->encode());
            }
            if ($prevCursor) {
                $config->setPrevCursor($prevCursor->encode());
            }

            return $paginator->items();
        });



        $taskInfoIds = [];
        foreach ($bareTasks as $bareTask) {
            $taskInfoIds[$bareTask->taskInfoId] = true;
        }
        $taskInfoIds = array_keys($taskInfoIds);
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        $taskReviewIdAndScoreByTaskInfoId =  UserHelper::tryGetUserId() === null ? []
         : TaskHelper::getTaskReviewIdsAndScoreByTaskInfoId($taskInfoIds);
        unset($taskInfoIds);

        $tasks = $bareTasks->map(function (BareListTask $task, $key) use (&$tagsByTaskInfoId, $taskReviewIdAndScoreByTaskInfoId): TaskPreviewInfo {
            $info = TaskPreviewInfo::create()
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setAuthor(
                    AuthorInfo::create()
                        ->setId(ResponseHelper::translateIdForUser($task->authorId))
                        ->setName($task->authorName)
                )
                ->setDifficulty(
                    DtoUtils::createOrderedEnumDto($task->difficulty)
                )
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setMin(DtoUtils::createOrderedEnumDto($task->minClass))
                        ->setMax(DtoUtils::createOrderedEnumDto($task->maxClass))
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
            $taskReviewIdAndScore =  $taskReviewIdAndScoreByTaskInfoId[$task->taskInfoId] ?? null;
            if ($taskReviewIdAndScore) {
                $info->setTaskReview(
                    TaskPreviewInfoTaskReview::create()
                        ->setId(ResponseHelper::translateIdForUser($taskReviewIdAndScore[0]))
                        ->setScore($taskReviewIdAndScore[1])
                );
            }
            return $info;
        })->all();

        return List\ListTasksResponse::create()
            ->setTasks($tasks)
            ->setConfig($config);
    }

    public function detail(HttpRequest $request, int $taskId): Detail\TaskDetailResponse
    {
        $task = BareDetailTask::tryFetchPublic($taskId);
        if (!$task) {
            throw new AppModelNotFoundException('Task', withProperties: ['id' => $taskId]);
        }
        $tags = TaskHelper::getTaskInfoTags($task->taskInfoId);

        $taskDetailInfo = TaskDetailInfo::create()
            ->setId(ResponseHelper::translateIdForUser($task->id))
            ->setName($task->name)
            ->setVersion($task->version . '')
            ->setDescription($task->description)
            ->setAuthor(
                AuthorInfo::create()
                    ->setId(ResponseHelper::translateIdForUser($task->authorId))
                    ->setName($task->authorName)
            )
            ->setClassRange(
                ResponseOrderedEnumRange::create()
                    ->setMin(DtoUtils::createOrderedEnumDto($task->minClass))
                    ->setMax(DtoUtils::createOrderedEnumDto($task->maxClass))
            )
            ->setDifficulty(
                ResponseOrderedEnumElement::create()
                    ->setOrderedId($task->difficulty->value)
                    ->setName($task->difficulty->name)
            )
            ->setTags(array_values($tags->map(
                fn (string $tag, int $tagId) =>
                ResponseEnumElement::create()
                    ->setId(ResponseHelper::translateIdForUser($tagId))
                    ->setName($tag)
            )->all()));
        $userId = UserHelper::tryGetUserId();
        if ($userId !== null) {
            $taskReviewTable = TaskReviewConstants::TABLE_NAME;
            $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
            $taskReview = DB::table($taskReviewTable)
                ->select([
                    DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                    DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE)
                ])
                ->join(
                    $taskReviewTemplateTable,
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                    '=',
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID)
                )
                ->where(
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                    '=',
                    $task->taskInfoId
                )->where(
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                    '=',
                    $userId
                )
                ->first();
            if ($taskReview !== null) {
                $taskDetailInfo->setTaskReview(
                    TaskDetailInfoTaskReview::create()
                        ->setId(ResponseHelper::translateIdForUser(
                            DBHelper::access($taskReview, TaskReviewConstants::COL_ID)
                        ))
                        ->setScore(
                            DBHelper::access($taskReview, TaskReviewConstants::COL_SCORE)
                        )
                );
            }
        }

        return TaskDto\Detail\TaskDetailResponse::create()
            ->setTask($taskDetailInfo);
    }

    public function delete(HttpRequest $request, int $taskId): Response
    {
        DB::transaction(function () use ($taskId) {
            $userId = UserHelper::getUserId();
            $taskTable = TaskConstants::TABLE_NAME;
            $taskInfoTable = TaskInfoConstants::TABLE_NAME;
            $task = DB::table($taskTable)
                ->select([
                    DBHelper::colFromTableAsCol($taskTable, TaskConstants::COL_TASK_INFO_ID),
                    DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_TASK_SOURCE_ID)
                ])
                ->join(
                    TaskInfoConstants::TABLE_NAME,
                    DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_ID),
                    '=',
                    DBHelper::tableCol(TaskConstants::TABLE_NAME, TaskConstants::COL_TASK_INFO_ID)
                )
                ->where(
                    DBHelper::tableCol($taskTable,TaskConstants::COL_ID),
                     '=',
                      $taskId
                      )
                ->where(
                    DBHelper::tableCol($taskTable,TaskConstants::COL_USER_ID),
                    '=',
                    $userId
                )
                ->first() ?? throw new AppModelNotFoundException("Task", ['id' => $taskId]);
                  //  DebugLogger::log("Task with id $taskId found!");
            $taskInfoId = DBHelper::access($task, TaskConstants::COL_TASK_INFO_ID);
            $taskSourceId = DBHelper::access($task, TaskInfoConstants::COL_TASK_SOURCE_ID);

            $deleted = DB::table(TaskConstants::TABLE_NAME)
            ->where(TaskConstants::COL_ID,'=',$taskId)
            ->where(
                TaskConstants::COL_USER_ID,
                '=',
                $userId
            )
                ->delete();
               // DebugLogger::log("Delete task with taskId $taskId and userId $userId deleted",['res'=>$deleted]);
            if ($deleted === 0) {
                //DebugLogger::log("Task delete '{$taskId}' - returns 0");
                // There are some concurrent query, so let it to do the rest
                return;
            }

            $taskReviewTemplateExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                ->exists();


            if (!$taskReviewTemplateExists) {
                DB::table(TaskInfoConstants::TABLE_NAME)
                    ->delete($taskInfoId);

                // Try to delete task source if it is not referenced elsewhere
                try {
                    DB::table(TaskSourceConstants::TABLE_NAME)
                        ->delete($taskSourceId);
                } catch (Throwable $e) {
                }
                // There is no need to delete groups, tags, or exercises,
                // because all of them should be deleted by cascade if deletetion of the task source succeeds
            } else {
                // Actual exercises are not needed if there is no task that would use them
                // because for now task source cannot be used by more than one task, 
                // so there is no need to check if there is not any other task that uses this task source
                TaskHelper::deleteActualExercisesByTaskSource($taskSourceId);
            }
        });
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function myDetail(HttpRequest $request, int $taskId): MyDetail\MyTaskDetailResponse
    {
        $task = BareTask::tryFetchById($taskId);
        if (!$task) {
            throw new AppModelNotFoundException('Task', withProperties: ['id' => $taskId]);
        }
        $tags = TaskHelper::getTaskInfoTags($task->taskInfoId);

        $taskCreationTimestamp = TimeStampUtils::timestampToString($task->createdAt);
        $taskModificationTimestamp = $task->updatedAt ? 
        TimeStampUtils::timestampToString($task->updatedAt)
         : $taskCreationTimestamp;
        return MyDetail\MyTaskDetailResponse::create()
            ->setTask(
                MyTaskDetailInfo::create()
                    ->setId(ResponseHelper::translateIdForUser($task->id))
                    ->setName($task->name)
                    ->setVersion($task->version . '')
                    ->setCreationTimestamp($taskCreationTimestamp)
                    ->setModificationTimestamp($taskModificationTimestamp)
                    ->setOrientation(match ($task->display) {
                        TaskDisplay::HORIZONTAL => MyTaskDetailInfo::HORIZONTAL,
                        TaskDisplay::VERTICAL => MyTaskDetailInfo::VERTICAL,
                        default => throw new UnsupportedVariantException($task->display)
                    })
                    ->setClassRange(
                        ResponseOrderedEnumRange::create()
                            ->setMin(DtoUtils::createOrderedEnumDto($task->minClass))
                            ->setMax(DtoUtils::createOrderedEnumDto($task->maxClass))
                    )
                    ->setDifficulty(
                        ResponseOrderedEnumElement::create()
                            ->setOrderedId($task->difficulty->value)
                            ->setName($task->difficulty->name)
                    )
                    ->setTags(array_values($tags->map(
                        fn (string $tag, int $tagId) =>
                        ResponseEnumElement::create()
                            ->setId(ResponseHelper::translateIdForUser($tagId))
                            ->setName($tag)
                    )->all()))
                    ->setIsPublic($task->isPublic)
            );
    }

    public function myList(HttpRequest $request): MyList\ListMyTasksResponse
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(MyList\ListMyTasksRequest::class, $request);
        $config = ListConfig::create();

        $bareTasks = BareTask::tryFetch(function (Builder $builder) use ($requestData, $userId,$config) {
            /**
             * @var MyList\Errors\FilterErrorDetailsErrorData|null $error
             */
            $filterErrorData = null;
            $builder->where(TaskConstants::COL_USER_ID, $userId);

            $filters = $requestData->filters;
            if ($filters) {
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
                        DBHelper::tableCol(TaskConstants::TABLE_NAME, TaskConstants::COL_NAME)
                            . " LIKE ?",
                        ["%{$filters->name}%"]
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
                    $rangeError = TaskHelper::filterByModificationTimestamp($modificationRange, $builder,withPrefix:true);
                    if ($rangeError) {
                        ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                            ->setModificationTimestampRange($rangeError);
                    }
                }
                if (($creationRange = $filters->creationTimestampRange)) {
                    $rangeError = TaskHelper::filterByCreationTimestamp($creationRange, $builder,withPrefix:true);
                    if ($rangeError) {
                        ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                            ->setCreationTimestampRange($rangeError);
                    }
                }
            }

            if ($filterErrorData) {
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
                    ApplicationErrorInformation::create()
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
            if($requestData->orderBy){
            $transformOrderBy = function (array $orderBy) {
                /**
                 * @var MyList\MyListRequestOrderByItems[] $orderBy
                 */
                foreach ($orderBy as $filterAndOrder) {
                    yield $filterAndOrder->filterName =>
                        $filterAndOrder->type === MyList\MyListRequestOrderByItems::DESC ? 'DESC' : 'ASC';
                }
            };

            
            TaskHelper::distinctOrderBy(
                $transformOrderBy($requestData->orderBy),
                function (string $filterName, $direction) use ($builder) {
                    if ($filterName === MyList\MyListRequestOrderByItems::CLASS_RANGE) {
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
                        if ($filterName === MyList\MyListRequestOrderByItems::DIFFICULTY) {
                            $column = DBHelper::tableCol(
                                TaskInfoConstants::TABLE_NAME,
                                TaskInfoConstants::COL_DIFFICULTY
                            );
                        } else if ($filterName === MyList\MyListRequestOrderByItems::NAME) {
                            $column = DBHelper::tableCol(
                                TaskConstants::TABLE_NAME,
                                TaskConstants::COL_NAME
                            );
                        } else if ($filterName === MyList\MyListRequestOrderByItems::CREATION_TIMESTAMP){
                            $column = DBHelper::tableCol(
                                TaskConstants::TABLE_NAME,
                                TaskConstants::COL_CREATED_AT
                            );
                        } else if ($filterName === MyList\MyListRequestOrderByItems::MODIFICATION_TIMESTAMP){
                            $column = DBHelper::tableCol(
                                TaskConstants::TABLE_NAME,
                                TaskConstants::COL_UPDATED_AT
                            );
                        } else {
                            return false;
                        }
                       // DebugLogger::log("TaskController::myList - orderBy",[$column,$direction]);
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        }

            //Log::debug("TaskController - myList - Executed query: '" . $builder->toRawSql() . "");
            $paginator = $builder->orderBy(TaskConstants::COL_ID)
                ->cursorPaginate(
                    perPage: $requestData->options->limit,
                    cursor: $requestData->options->cursor
                );
            $nextCursor = $paginator->nextCursor();
            $prevCursor = $paginator->previousCursor();
            if ($nextCursor) {
                $config->setNextCursor($nextCursor->encode());
            }
            if ($prevCursor) {
                $config->setPrevCursor($prevCursor->encode());
            }

            return $paginator->items();
        });
        $taskInfoIds = $bareTasks->map(fn (BareTask $bareTask) => $bareTask->taskInfoId)
            ->all();
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        unset($taskInfoIds);

        $tasks = $bareTasks->map(function (BareTask $task, $key) use (&$tagsByTaskInfoId): MyTaskPreviewInfo {
            $info = MyTaskPreviewInfo::create()
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setIsPublic($task->isPublic)
                ->setCreationTimestamp(TimeStampUtils::timestampToString($task->createdAt));
            if (($updatedAt = $task->updatedAt)) {
                $info->setModificationTimestamp(TimeStampUtils::timestampToString($updatedAt));
            }
            $info->setDifficulty(
                DtoUtils::createOrderedEnumDto($task->difficulty)
            )
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setMin(DtoUtils::createOrderedEnumDto($task->minClass))
                        ->setMax(DtoUtils::createOrderedEnumDto($task->maxClass))
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

        return MyList\ListMyTasksResponse::create()
            ->setTasks($tasks)
            ->setConfig($config);
    }
}
