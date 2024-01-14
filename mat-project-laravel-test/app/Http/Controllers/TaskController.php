<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Dtos\Task as TaskDto;
use App\Dtos\Task\Create;
use App\Helpers\CreateTask\ParseEntry\ParseEntry;
use App\Helpers\CreateTask\TaskRes;
use Illuminate\Http\Request as HttpRequest;
use App\Dtos\Task\Take;
use App\Exceptions\InternalException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\RequestHelper;
use App\TableSpecificData\TaskDisplay;
use App\Helpers\ExerciseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Exercise;
use App\Models\Group;
use App\Models\Resource;
use App\Utils\Utils;
use Carbon\Carbon;
use DateTimeZone;
use Swaggest\JsonSchema\Structure\ClassStructure;

class TaskController extends Controller
{

    public static function construct():static{
        return new static();
    }

    
    public function take(HttpRequest $request, int $id): Take\Response\Response
    {
        $requestData = Take\Request\Request::import(RequestHelper::getData($request));
        $taskId = $id;
        $responseTask = Take\Response\Task::create();
        $task = Task::whereId($id)->get()->firstOrFail();


        $responseTask->setName($task->name);
        $responseTask->setDescription($task->description);
        $taskDisplay = Task::getOrientation($task);
        $responseTask->setDisplay(match ($taskDisplay) {
            TaskDisplay::HORIZONTAL => Take\Response\Task::HORIZONTAL,
            TaskDisplay::VERTICAL => Take\Response\Task::VERTICAL,
            default => throw new UnsupportedVariantException($taskDisplay)
        });

        $localySavedTaskTimeStamp = Carbon::createFromTimestamp(
            timestamp:$requestData->localySavedTask->timestamp,
            tz:DateTimeZone::UTC
        )->setTimezone(DateTimeZone::UTC);
        $exercises = ExerciseHelper::take($taskId,localySavedTaskUtcTimestamp:$localySavedTaskTimeStamp);

        $groupIdName = Group::getPrimaryKeyName();
        $groups = DB::table(Group::getTableName())
            ->select([$groupIdName, Group::START, Group::LENGTH])
            ->where(Group::TASK_ID, '=', $taskId)
            ->orderBy(Group::START, direction: 'asc')
            ->orderBy(Group::LENGTH, direction: 'desc')
            ->get();

        $resources = DB::table(Resource::getTableName())
            ->select([Resource::GROUP_ID, Resource::CONTENT])
            ->whereIn(Resource::GROUP_ID, [$groups->keys()])
            ->get();
        /**
         * @var array<mixed,string[]> $resourcesByGroupId
         */
        $resourcesByGroupId = [];
        while (($resource = $resources->pop()) !== null) {
            /**
             * @var mixed $groupId
             */
            $groupId = $resource[Resource::GROUP_ID];
            /**
             * @var string $content
             */
            $content = $resource[Resource::CONTENT];
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
        $dest = &$responseTask->entries;
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
                            'destination' => $dest,
                            'task' => $responseTask
                        ]
                    );
                }

                [$exerciseEnd, &$dest] =  $stack[$stackEntryKey];
                unset($stack[$stackEntryKey]);
            }
            if ($exI === $nextGroup[Group::START]) {
                $groupId = $nextGroup[$groupIdName];
                $groupDto = Take\Response\DefsGroup::create()
                    ->setResources(array_map(fn (string $resource) =>
                    Take\Response\DefsGroupResourcesItems::create()
                        ->setContent($resource), $resourcesByGroupId[$groupId]));
                unset($resourcesByGroupId[$groupId]);

                $dest[] = $groupDto;
                $stack[] = [$exerciseEnd, &$dest];
                $dest = &$groupDto->entries;
                $exerciseEnd = $exI + $nextGroup[Group::LENGTH];
            }
            $exerciseDto = Take\Response\DefsExercise::create()
                ->setInstructions(Take\Response\DefsExerciseInstructions::create()
                    ->setContent($exercise->instructions));
            $exercise->impl->setAsContentTo($exerciseDto);
            $dest[] = $exerciseDto;
        }
        return Take\Response\Response::create()
            ->setTask($responseTask);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HttpRequest $request): Create\Response\Response
    {
      $requestData = Create\Request\Request::import(RequestHelper::getData($request));
       $parseEnty = new ParseEntry();
      $taskRes = $parseEnty->parse([$requestData->task->source]);
      $taskRes->tagsIds = $requestData->task->tags;
     $taskId = $taskRes->insert();
      return Create\Response\Response::create()
      ->setTaskId($taskId);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        //
    }
}
