<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Errors\GeneralErrorDetails as ErrorsGeneralErrorDetails;
use App\Dtos\Defs\Types\Errors\EnumArrayError as ErrorsEnumArrayError;
use App\Dtos\Defs\Types\Errors\InvalidBoundsError;
use App\Dtos\Defs\Types\Errors\RangeError;
use App\Dtos\Defs\Types\Errors\RangeError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Defs\Types\MyTask\MyTaskDetailInfo;
use App\Dtos\Defs\Types\MyTask\MyTaskPreviewInfo;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
use App\Dtos\Defs\Types\Review\ExercisePoints;
use App\Dtos\Defs\Types\Task\AuthorInfo;
use App\Dtos\Defs\Types\Task\TaskDetailInfo;
use App\Dtos\Defs\Types\Task\TaskDetailInfoAuthor;
use App\Dtos\Defs\Types\Task\TaskPreviewInfo;
use App\Dtos\Defs\Types\Task\TaskPreviewInfoAuthor;
use App\Dtos\Errors\ErrorResponse;
use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Dtos\Task as TaskDto;
use App\Dtos\Task\Create;
use App\Dtos\Task\Evaluate;
use App\Dtos\Task\Review;
use App\Helpers\CreateTask\ParseEntry;
use App\Helpers\CreateTask\TaskRes;
use Illuminate\Http\Request as HttpRequest;
use App\Dtos\Task\Take;
use App\Dtos\Task\List;
use App\Dtos\Task\MyList;
use App\Dtos\Task\List\Error\EnumArrayError;
use App\Dtos\Task\List\Error\GeneralErrorDetails;
use App\Dtos\Task\List\Errors\FilterErrorDetails;
use App\Dtos\Task\List\Errors\FilterErrorDetailsErrorData;
use App\Dtos\Task\List\OrderByItems as ListOrderByItems;
use App\Dtos\Task\List\Request\OrderByItems;
use App\Dtos\Task\MyList\OrderByItems as MyListOrderByItems;
use App\Dtos\Task\Review\Get\DefsExercise;
use App\Dtos\Task\Review\Get\DefsExerciseInstructions as GetDefsExerciseInstructions;
use App\Dtos\Task\Take\DefsExerciseInstructions;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\ConversionException;
use App\Exceptions\EnumConversionException;
use App\Exceptions\InternalException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\BareModels\BareTask;
use App\Helpers\BareModels\BareTaskWAuthorName;
use App\Helpers\Database\DBHelper;
use App\Helpers\RequestHelper;
use App\TableSpecificData\TaskDisplay;
use App\Helpers\ExerciseHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TaskHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Exercise;
use App\Models\Group;
use App\Models\Resource;
use App\Models\Tag;
use App\Models\TagTask;
use App\Models\User;
use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\Types\EvaluateExercise;
use App\Types\TakeExercise;
use App\Utils\DBUtils;
use App\Utils\DebugUtils;
use App\Utils\DtoUtils;
use App\Utils\TimeStampUtils;
use App\Utils\Utils;
use App\Utils\ValidateUtils;
use Carbon\Carbon;
use Swaggest\JsonSchema;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Swaggest\JsonSchema\JsonSchema as JsonSchemaJsonSchema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class TaskController extends Controller
{

    public static function construct(): static
    {
        return new static();
    }


    public function take(HttpRequest $request, int $id): Take\Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Take\Request::class, $request);
        $taskId = $id;
        $responseTask = Take\Task::create();
        $task = BareTaskWAuthorName::tryFetchById($taskId, publicOnly: true)
            ?? throw new AppModelNotFoundException('Task', ['id' => $taskId]);

        $responseTask->setTaskDetail(TaskHelper::getInfo($task))
            ->setDisplay($task->display);

        DebugUtils::log("timestamp", $requestData->localySavedTask?->timestamp);
        // dump($requestData->localySavedTask?->timestamp);
        $localySavedTaskTimeStamp = $requestData->localySavedTask ?
            Carbon::createFromFormat(
                format: DateTime::ATOM,
                time: $requestData->localySavedTask->timestamp,
                timezone: new DateTimeZone('UTC')
            ) : null;
        DebugUtils::log("localySavedTaskTimeStamp", $localySavedTaskTimeStamp);
        $exercises = ExerciseHelper::take(
            taskId: $taskId,
            localySavedTaskUtcTimestamp: $localySavedTaskTimeStamp
        );
        $responseTask->entries = [];
        $taskEntries = &$responseTask->entries;
        TaskHelper::getTaskEntries(
            taskId: $taskId,
            exercises: $exercises,
            entries: $taskEntries,
            groupToDto: function (array $resources) {
                $groupDto = Take\DefsGroup::create()
                    ->setResources(array_map(fn (string $resource) =>
                    Take\DefsGroupResourcesItems::create()
                        ->setContent($resource), $resources));
                return $groupDto;
            },
            exerciseToDto: function (TakeExercise $exercise) {
                $exerciseDto =  Take\DefsExercise::create()
                    ->setInstructions(DefsExerciseInstructions::create()
                        ->setContent($exercise->instructions));
                $exercise->impl->setAsContentTo($exerciseDto);
                return $exerciseDto;
            }
        );
        return Take\Response::create()
            ->setTask($responseTask);
    }

    public function store(HttpRequest $request): Create\Response
    {
        DebugUtils::log("Task:store called", $request);
        $requestData = RequestHelper::getDtoFromRequest(Create\Request::class, $request);
        DebugUtils::log("Task:store requestData imported", $requestData);
        $parseEnty = new ParseEntry();
        DebugUtils::log('Task:store parse');
        $taskRes = $parseEnty->parse([$requestData->task->source]);
        DebugUtils::log('Task:store parsed');
        $taskRes->tagsIds = $requestData->task->tags;
        $taskId = $taskRes->insert();
        DebugUtils::log('Task:store completed');
        return Create\Response::create()
            ->setTaskId($taskId);
    }

    public function update(HttpRequest $request,int $id):Response{
        $requestData = RequestHelper::getDtoFromRequest(TaskDto\Update\Request::class,$request);
        $parseEntry = new ParseEntry();
        $taskRes = $parseEntry->parse([$requestData->task->source]);
        $taskRes->tagsIds = $requestData->task->tags;
        $taskRes->update();
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function evaluate(HttpRequest $request, int $id):TaskDto\Review\Get\Response
    {
        $requestData = RequestHelper::getDtoFromRequest(Evaluate\Request::class, $request);
        $taskId = $id;

        $task = BareTaskWAuthorName::tryFetchById($id, publicOnly: true)
            ?? throw new AppModelNotFoundException('Task', ['id' => $id]);

        $responseTask = Review\Get\Task::create()
            ->setDisplay($task->display);

        $exercises = ExerciseHelper::evaluate(
            taskId: $taskId
        );
        $taskPoints = 0;
        $taskmax = 0;
        $taskEntries = &$responseTask->setEntries([])->entries;
        TaskHelper::getTaskEntries(
            taskId: $taskId,
            exercises: $exercises,
            exerciseToDto: function (EvaluateExercise $exercise) use ($requestData, &$taskPoints, &$taskmax) {
                $exerciseDto = DefsExercise::create()
                    ->setInstructions(GetDefsExerciseInstructions::create()
                        ->setContent($exercise->instructions));

                $exerciseValue = array_shift($requestData->exercises);
                $exercise->impl->evaluateAndSetAsContentTo($exerciseValue, $exerciseDto);

                $exerciseDto->points->has = ($exerciseDto->points->has * $exercise->weight) / $exerciseDto->points->max;
                $exerciseDto->points->max = $exercise->weight;

                $taskPoints += $exerciseDto->points->has;
                $taskmax += $exerciseDto->points->max;
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
            $builder->where(Task::USER_ID, Auth::getUser()->id);

            $filters = $requestData->filters;
            if ($filters->tags) {
                $invalidTags = TaskHelper::filterByTags($filters->tags, $builder);
                if ($invalidTags) {
                    ($filterErrorData ??= List\Errors\FilterErrorDetailsErrorData::create())
                        ->setTags(
                            ErrorsEnumArrayError::create()
                                ->setMessage("Invalid ids specified.")
                        );
                }
            }

            if ($filters->name) {
                $builder->whereRaw(TASK::NAME . " LIKE %?%", [$filters->name]);
            }

            if ($filters->difficultyRange) {
                $RangeError = TaskHelper::filterByDifficultyRange(
                    min: $filters->difficultyRange->min,
                    max: $filters->difficultyRange->max,
                    builder: $builder
                );
                if ($RangeError) {
                    ($filterErrorData ??= List\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filters->classRange) {
                $RangeError = TaskHelper::filterByClassRange(
                    min: $filters->classRange->min,
                    max: $filters->classRange->max,
                    builder: $builder
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
                        $builder->orderBy(Task::MIN_CLASS, $direction);
                        $builder->orderBy(Task::MAX_CLASS, $direction);
                    } else {
                        if ($filterName === ListOrderByItems::DIFFICULTY) {
                            $column = Task::DIFFICULTY;
                        } else if ($filterName === ListOrderByItems::NAME) {
                            $column = Task::NAME;
                        } else {
                            return false;
                        }
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        });
        $tagsByTaskId = TaskHelper::getTagsByTaskId();

        $tasks = $bareTasks->map(function (BareTaskWAuthorName $task, $key) use (&$tagsByTaskId): TaskPreviewInfo {
            $info = TaskPreviewInfo::create()
                ->setId(ResponseHelper::translateIdForUser($task->id))
                ->setName($task->name)
                ->setAuthor(
                    TaskPreviewInfoAuthor::create()
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
                    }, $tagsByTaskId[$task->id])
                );
            return $info;
        })->all();

        return List\Response::create()
            ->setTasks($tasks);
    }

    public function detail(HttpRequest $request,int $taskId):TaskDto\Detail\Response{
       $task = BareTaskWAuthorName::tryFetchById($taskId,publicOnly:true);
       if(!$task){
        throw new AppModelNotFoundException(Task::class,withProperties:['id'=>$taskId]));
       }
       $tags = TaskHelper::getTaskTags($taskId);

        return TaskDto\Detail\Response::create()
        ->setTask(TaskDetailInfo::create()
        ->setId($task->id)
        ->setName($task->name)
        ->setVersion($task->version.'')
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
        ->setTags($tags->map(fn(string $tag,int $tagId)=>
        ResponseEnumElement::create()
        ->setId(ResponseHelper::translateIdForUser($tagId))
        ->setName($tag)
        ))
    );
    }

    public function delete(HttpRequest $request, int $taskId):Response
    {
        if (!DB::table(Task::getTableName())
            ->delete($taskId)) {
            throw new AppModelNotFoundException("Task", ['id' => $taskId]);
        }
        return response(status: Response::HTTP_NO_CONTENT);
    }

    public function myDetail(HttpRequest $request,int $taskId):TaskDto\MyDetail\Response
    {
        $task = BareTask::tryFetchById($taskId,publicOnly:true);
        if(!$task){
         throw new AppModelNotFoundException(Task::class,withProperties:['id'=>$taskId]));
        }
        $tags = TaskHelper::getTaskTags($taskId);
 
         return TaskDto\MyDetail\Response::create()
         ->setTask(MyTaskDetailInfo::create()
         ->setId($task->id)
         ->setName($task->name)
         ->setVersion($task->version.'')
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
         ->setTags($tags->map(fn(string $tag,int $tagId)=>
         ResponseEnumElement::create()
         ->setId(ResponseHelper::translateIdForUser($tagId))
         ->setName($tag)
         ))
     );
    }
    
     public function myList(HttpRequest $request):TaskDto\MyList\Response
    {

        $requestData = RequestHelper::getDtoFromRequest(MyList\Request::class, $request);

        $bareTasks = BareTask::tryFetch(function (Builder $builder) use ($requestData) {
            /**
             * @var MyList\Errors\FilterErrorDetailsErrorData|null $error
             */
            $filterErrorData = null;
            $builder->where(Task::USER_ID, Auth::getUser()->id);

            $filters = $requestData->filters;
            if ($filters->tags) {
                $invalidTags = TaskHelper::filterByTags($filters->tags, $builder);
                if ($invalidTags) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setTags(
                            ErrorsEnumArrayError::create()
                                ->setMessage("Invalid ids specified.")
                        );
                }
            }

            if ($filters->name) {
                $builder->whereRaw(TASK::NAME . " LIKE %?%", [$filters->name]);
            }

            if ($filters->difficultyRange) {
                $RangeError = TaskHelper::filterByDifficultyRange(
                    min: $filters->difficultyRange->min,
                    max: $filters->difficultyRange->max,
                    builder: $builder
                );
                if ($RangeError) {
                    ($filterErrorData ??= MyList\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filters->classRange) {
                $RangeError = TaskHelper::filterByClassRange(
                    min: $filters->classRange->min,
                    max: $filters->classRange->max,
                    builder: $builder
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
                        $builder->orderBy(Task::MIN_CLASS, $direction);
                        $builder->orderBy(Task::MAX_CLASS, $direction);
                    } else {
                        if ($filterName === MyListOrderByItems::DIFFICULTY) {
                            $column = Task::DIFFICULTY;
                        } else if ($filterName === MyListOrderByItems::NAME) {
                            $column = Task::NAME;
                        } else {
                            return false;
                        }
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        });
        $tagsByTaskId = TaskHelper::getTagsByTaskId();

        $tasks = $bareTasks->map(function (BareTask $task, $key) use (&$tagsByTaskId): MyTaskPreviewInfo {
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
                    }, $tagsByTaskId[$task->id])
                );
            return $info;
        })->all();

        return MyList\Response::create()
            ->setTasks($tasks);
    }
}
