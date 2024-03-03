<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Endpoints\Task\Review\Get\GetResponseTask;
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
use App\Dtos\Defs\Endpoints\Task\Save;
use App\Dtos\Defs\Endpoints\Task\Update;
use App\Dtos\Defs\Endpoints\Task\Detail;
use App\Dtos\Defs\Endpoints\Task\Evaluate;
use App\Dtos\Defs\Endpoints\Task\Evaluate\Errors\MismatchedExerciseValueEvaluateError;
use App\Dtos\Defs\Endpoints\Task\Evaluate\EvaluateResponseTask;
use App\Dtos\Defs\Endpoints\Task\List;
use App\Dtos\Defs\Endpoints\Task\MyList;
use App\Dtos\Defs\Endpoints\Task\Review;
use App\Dtos\Defs\Endpoints\Task\Take;
use App\Dtos\Defs\Endpoints\Task\MyDetail;
use App\Dtos\Defs\Endpoints\Task\Take\DefsExerciseInstructions;
use App\Dtos\Defs\Endpoints\Task\Take\NewerServerSavedTaskInfo;
use App\Dtos\Defs\Endpoints\Task\Take\OlderServerSavedTaskInfo;
use App\Dtos\Defs\Endpoints\Task\Take\SavedTaskValues;
use App\Dtos\Defs\Endpoints\Task\Take\TakeResponseTaskTaskDetail;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidEvaluateValueException;
use App\Exceptions\UnPreparedCaseException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\BareModels\BareDetailTask;
use App\Helpers\BareModels\BareEvaluateTask;
use App\Helpers\BareModels\BareListTask;
use App\Helpers\BareModels\BareTakeTask;
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
use App\MyConfigs\TaskSrcConfig;
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
use Illuminate\Support\Facades\Log;
use Swaggest\JsonSchema\InvalidValue;

class TaskController extends Controller
{

    /**
     * @throws ApplicationException
     * @throws AppModelNotFoundException
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
            ->setDisplay(match($task->orientation){
                TaskDisplay::HORIZONTAL => Take\TakeResponseTask::HORIZONTAL,
                TaskDisplay::VERTICAL => Take\TakeResponseTask::VERTICAL,
                default => throw new UnsupportedVariantException($task->orientation)
            });

        DebugUtils::log("timestamp", $requestData->localySavedTask?->timestamp ?? null);
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
    Log::debug("TakeTaskResponse: ",['dto'=>DtoUtils::exportDto(Take\TakeTaskResponse::create()
    ->setTask($responseTask))]);
        return Take\TakeTaskResponse::create()
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
        $requestData = RequestHelper::getDtoFromRequest(Save\SaveTaskRequest::class, $request);
        $success = DB::table(SavedTask::getTableName())
            ->updateOrInsert(
                attributes: [
                    SavedTaskConstants::COL_TASK_ID => $id,
                    SavedTaskConstants::COL_USER_ID => $userId
                ],
                values: [
                    SavedTaskConstants::COL_DATA => Save\SaveTaskRequest::export($requestData->exercises)
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
        $task->tagIds = array_map(
            fn ($tagId) => RequestHelper::translateId($tagId),
            $requestTask->tags
        );
        $task->difficulty = TaskDifficulty::fromThrow($requestTask->difficulty);
        $task->isPublic = $requestTask->isPublic;
        $task->minClass = TaskClass::fromThrow($requestTask->classRange->min);
        $task->maxClass = TaskClass::fromThrow($requestTask->classRange->max);

        $taskId = $taskRes->insert($requestData->task->source);
        return Create\TaskCreateResponse::create()
            ->setTaskId(ResponseHelper::translateIdForUser($taskId));
    }

    public function update(HttpRequest $request, int $id): Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Update\TaskUpdateRequest::class, $request);
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

        $taskRes->update($id,$requestData->task->source);
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function evaluate(HttpRequest $request, int $id): Review\Get\ReviewTaskResponse
    {
        $userId = UserHelper::tryGetUserId();
        $requestData = RequestHelper::getDtoFromRequest(Evaluate\EvaluateTaskRequest::class, $request);
        $responseTask = EvaluateResponseTask::create();
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
        $do = function (array|null &$evaluatedExercises, &$responseTask) use ($id, $userId, $requestData) {
            $task = BareEvaluateTask::tryFetchPublic($id)
                ?? throw new AppModelNotFoundException('Task', ['id' => $id]);
            if ($task->version !== RequestHelper::translateId($requestData->version)) {
                Log::debug("Task version has changed: ", [
                    'requestVersion' => $requestData->version,
                    'taskVersion' => $task->version
                ]);
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
            ->setEvaluationTimestamp(TimeStampUtils::timestampNowUtcString())
            ->setDisplay(match ($task->orientation) {
                TaskDisplay::HORIZONTAL => EvaluateResponseTask::HORIZONTAL,
                TaskDisplay::VERTICAL => EvaluateResponseTask::VERTICAL,
                default => throw new UnsupportedVariantException($task->orientation)
            });

            $exercises = ExerciseHelper::evaluateTaskInfo(
                taskInfoId: $task->taskInfoId
            );

            $taskPoints = 0;
            $taskmax = 0;
            $taskEntries = &$responseTask->setEntries([])->entries;
            TaskHelper::getTaskEntries(
                taskInfoId: $task->taskInfoId,
                exercises: $exercises,
                exerciseToDto: function (EvaluateExercise $exercise, int $i) use ($requestData, &$taskPoints, &$taskmax, &$evaluatedExercises) {
                    Log::info("exerciseToDto - task evaluate");
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
                        Log::info("push evaluated exercise to evaluated exercises");
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
            DB::transaction(function () use ($do, $userId, $responseTask) {
                /**
                 * @var ExerciseReview[] $evaluatedExercises
                 */
                $evaluatedExercises = [];
                $task = $do($evaluatedExercises, $responseTask);
                Log::info('TaskEvaluate - evaluated exercises: ',['exercises'=>$evaluatedExercises]);
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
                            TaskReviewTemplateConstants::COL_TASK_ID => $task->id,
                            TaskReviewTemplateConstants::COL_TASK_INFO_ID => $task->taskInfoId
                        ]);
                }

                $exercises = TaskReviewExercisesContent::create()
                    ->setContent($evaluatedExercises);
                $taskReviewData = [
                    TaskReviewConstants::COL_USER_ID => $userId,
                    TaskReviewConstants::COL_EVALUATED_AT => $responseTask->evaluationTimestamp,
                    TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID => $templateId,
                    TaskReviewConstants::COL_MAX_POINTS => $responseTask->points->max,
                    TaskReviewConstants::COL_SCORE => $responseTask->points->has / $responseTask->points->max,
                    TaskReviewConstants::COL_EXERCISES => DtoUtils::dtoToJson(
                        dto: $exercises,
                        field: TaskReviewExercisesContent::CONTENT
                    )
                ];

                $inserted = DB::table(TaskReviewConstants::TABLE_NAME)
                    ->updateOrInsert([
                        TaskReviewConstants::COL_USER_ID => $userId,
                        TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID => $templateId
                    ],$taskReviewData);
                if (!$inserted) {
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

        $bareTasks = BareListTask::tryFetchPublic(function (Builder $builder) use ($requestData) {
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
        foreach ($bareTasks as $bareTask) {
            $taskInfoIds[$bareTask->taskInfoId] = true;
        }
        $taskInfoIds = array_keys($taskInfoIds);
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        $taskReviewIdByTaskInfoId =  UserHelper::tryGetUserId() === null ? [] : TaskHelper::getTaskReviewIdsByTaskInfoId($taskInfoIds);
        unset($taskInfoIds);

        $tasks = $bareTasks->map(function (BareListTask $task, $key) use (&$tagsByTaskInfoId, $taskReviewIdByTaskInfoId): TaskPreviewInfo {
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
            $taskReviewId =  $taskReviewIdByTaskInfoId[$task->taskInfoId] ?? null;
            if ($taskReviewId) {
                $info->setTaskReviewId(
                    ResponseHelper::translateIdForUser($taskReviewId)
                );
            }
            return $info;
        })->all();

        return List\ListTasksResponse::create()
            ->setTasks($tasks);
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
            ->setTags($tags->map(
                fn (string $tag, int $tagId) =>
                ResponseEnumElement::create()
                    ->setId(ResponseHelper::translateIdForUser($tagId))
                    ->setName($tag)
            ));
        $userId = UserHelper::tryGetUserId();
        if ($userId !== null) {
            $taskReviewTable = TaskReviewConstants::TABLE_NAME;
            $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
            $taskReviewId = DB::table($taskReviewTable)
                ->select([
                    DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID)
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
                ->value(TaskReviewConstants::COL_ID);
            if ($taskReviewId !== null) {
                $taskDetailInfo->setTaskReviewId(
                    ResponseHelper::translateIdForUser($taskReviewId)
                );
            }
        }

        return TaskDto\Detail\TaskDetailResponse::create()
            ->setTask($taskDetailInfo);
    }

    public function delete(HttpRequest $request, int $taskId): Response
    {
        DB::transaction(function () use ($taskId) {
            $taskInfoId = DB::table(TaskConstants::TABLE_NAME)
                ->select([TaskConstants::COL_TASK_INFO_ID])
                ->where(TaskConstants::COL_ID, '=', $taskId)
                ->value(TaskConstants::COL_TASK_INFO_ID);
            if ($taskInfoId === null) {
                throw new AppModelNotFoundException("Task", ['id' => $taskId]);
            }

            $deleted = DB::table(TaskConstants::TABLE_NAME)
                ->delete($taskId);
            if ($deleted === 0) {
                // we have there some concurrent query, so we let it to do the rest
                return;
            }

            $taskReviewTemplateExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                ->exists();


            if (!$taskReviewTemplateExists) {
                DB::table(TaskInfoConstants::TABLE_NAME)
                    ->delete($taskInfoId);
                // We do not need to delete groups, tags, or exercises,
                // because all of them should be deleted by cascade
            } else {
                TaskHelper::deleteActualExercisesByTaskInfo($taskInfoId);
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

        return MyDetail\MyTaskDetailResponse::create()
            ->setTask(
                MyTaskDetailInfo::create()
                    ->setId(ResponseHelper::translateIdForUser($task->id))
                    ->setName($task->name)
                    ->setVersion($task->version . '')
                    ->setCreationTimestamp(TimeStampUtils::timestampToString($task->createdAt))
                    ->setModificationTimestamp(TimeStampUtils::timestampToString($task->updatedAt))
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
                    ->setTags($tags->map(
                        fn (string $tag, int $tagId) =>
                        ResponseEnumElement::create()
                            ->setId(ResponseHelper::translateIdForUser($tagId))
                            ->setName($tag)
                    ))
            );
    }

    public function myList(HttpRequest $request): MyList\ListMyTasksResponse
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(MyList\ListMyTasksRequest::class, $request);

        $bareTasks = BareTask::tryFetch(function (Builder $builder) use ($requestData, $userId) {
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
        $taskInfoIds = $bareTasks->map(fn (BareTask $bareTask) => $bareTask->taskInfoId)
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
            ->setTasks($tasks);
    }
}
