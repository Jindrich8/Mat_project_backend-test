<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\Helpers\Database\PgDB;
    use App\Helpers\CCreateExerciseHelper;
    use App\Helpers\ExerciseHelper;
    use App\Helpers\BareModels\BareExercise;
    use App\Helpers\BareModels\BareGroup;
    use App\Models\Task;
    use App\Types\CCreateExerciseHelperStateEnum;
    use App\Utils\Utils;
    use App\Helpers\BareModels\BareResource;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\UserHelper;
    use App\Helpers\TaskHelper;
    use App\ModelConstants\ExerciseConstants;
    use App\ModelConstants\GroupConstants;
    use App\ModelConstants\ResourceConstants;
    use App\ModelConstants\TagTaskInfoConstants;
    use App\ModelConstants\TaskConstants;
    use App\ModelConstants\TaskReviewTemplateConstants;
    use App\Models\TaskInfo;
    use App\Types\DBTypeEnum;
    use App\Types\TaskResTask;
    use App\Types\XML\XMLDynamicNodeBase;
    use App\Types\XML\XMLNodeBase;
    use App\Utils\DBUtils;
    use App\Utils\DebugLogger;
    use DB;

    class TaskRes
    {
        public ?TaskResTask $task;

        /**
         * @var array<array{BareGroup,array<BareResource>}> $groupsAndResources
         */
        private array $groupsAndResources;

        private ?int $currentGroupIndex;

        private ?BareExercise $currentExercise;

        private int $exerciseCount;

        /**
         * @var array<string,array{CCreateExerciseHelper,BareExercise[]}> $exerciseHelpers
         */
        private array $exerciseHelpers;

        public function __construct()
        {
            $this->task = null;
            $this->groupsAndResources = [];
            $this->currentGroupIndex = null;
            $this->currentExercise = null;
            $this->exerciseCount = 0;
            $this->exerciseHelpers = [];
        }


        /**
         * Returns previous group index
         */
        public function addGroup(): ?int
        {
            $preGroupIndex = $this->currentGroupIndex;
            $this->currentGroupIndex = count($this->groupsAndResources);
            $group = new BareGroup(start:$this->getExerciseCount());
            $this->groupsAndResources[] = [$group, []];
            // dump($this->groupsAndResources);
            return $preGroupIndex;
        }

        public function getGroupCount()
        {
            return count($this->groupsAndResources);
        }

        public function getCurrentGroup(): BareGroup
        {
            $this->checkCurrentGroupAndItsResources();
            return $this->groupsAndResources[$this->currentGroupIndex][0];
        }

        private function checkCurrentGroupAndItsResources(): void
        {
            if (!$this->groupsAndResources || $this->currentGroupIndex === null) {
                throw new InternalException(
                    message: "There is no current group!",
                    context: ['taskRes' => $this]
                );
            }
        }

        public function addResourceToCurrentGroup(): void
        {
            $this->checkCurrentGroupAndItsResources();
            $this->groupsAndResources[$this->currentGroupIndex][1][] = new BareResource();
        }

        public function getLastResourceOfCurrentGroup(): BareResource
        {
            $this->checkCurrentGroupAndItsResources();
            $resources = &$this->groupsAndResources[$this->currentGroupIndex][1];
            $resource =  Utils::lastArrayValue($resources);
            if (!$resource) {
                throw new InternalException(
                    message: "Current group does not have resources.",
                    context: ['taskRes' => $this]
                );
            }
            return $resource;
        }

        public function getNumOfResourcesInCurrentGroup(): int
        {
            $this->checkCurrentGroupAndItsResources();
            $resources = &$this->groupsAndResources[$this->currentGroupIndex][1];
            return count($resources);
        }

        public function setCurrentGroupIndex(?int $groupIndex): void
        {
            if ($groupIndex !== null) {
                $groupCount = $this->getGroupCount();
                if ($groupIndex < 0 || $groupIndex >= $groupCount) {
                    throw new InternalException(
                        "Group index must be between 0 and $groupCount",
                        context: ['taskRes' => $this]
                    );
                }
            }
            $this->currentGroupIndex = $groupIndex;
        }

        public function addExercise()
        {
            ++$this->exerciseCount;
            if ($helper = $this->tryToGetHelper(addCurrentExercise: true)) {
                $state = $helper->getState();
                switch ($state) {
                    case CCreateExerciseHelperStateEnum::EXERCISE_ENDED:
                        break;

                    default:
                        throw new InternalException(
                            "BareExercise content was not properly initialized, but there is attempt to add another exercise.",
                            context: [
                                "ExerciseContentState" => $state,
                                "taskRes" => $this
                            ]
                        );
                }
            }
            $newExercise = new BareExercise();
            $newExercise->order = $this->exerciseCount - 1;
            $this->currentExercise = $newExercise;
        }

        public function getExerciseCount()
        {
            return $this->exerciseCount;
        }

        public function getExerciseContentNode(XMLNodeBase $parent, string $name): XMLDynamicNodeBase
        {
            $helper = $this->tryToGetHelper();
            if (!$helper) {
                throw new InternalException(
                    message: "There is no current exercise and helper!",
                    context: ["taskRes" => $this]
                );
            }
            return $helper->getContentNode(name: $name, parent: $parent);
        }

        /**
         * @return BareExercise
         */
        public function getLastExercise(): BareExercise
        {
            $last = $this->currentExercise;
            if (!$last) {
                throw new InternalException("There is no exercise and still something is trying to get it.");
            }
            return $last;
        }

        private function tryToGetHelper(bool $addCurrentExercise = false): ?CCreateExerciseHelper
        {
            $helper = null;
            if ($this->currentExercise) {
                $type = $this->currentExercise->exerciseType;
                $helperAndExercises = &$this->exerciseHelpers[$type->name];
                if (!$helperAndExercises) {
                    // throw new InternalException(
                    //     message: "There is no exercise helper for type '{$this->currentExercise->exerciseable_type}' but there is current exercise with this type.",
                    //     context: ['taskRes' => $this]
                    // );
                    $createHelper = ExerciseHelper::getHelper($type)->getCreateHelper();
                    $createHelper->reset();
                    $this->exerciseHelpers[$type->name] = [$createHelper, []];
                    $helperAndExercises = &$this->exerciseHelpers[$type->name];
                }
                if ($addCurrentExercise) {
                    $helperAndExercises[1][] = $this->currentExercise;
                }
                $helper = $helperAndExercises[0];
            }
            return $helper;
        }

        private function insertTaskInfoAndContent(?int $taskInfoId = null):int{
             // insert task info and tags
             {
                 $taskInfo = new TaskInfo();
                 if($taskInfoId !== null){
                    $taskInfo->id = $taskInfoId;
                 }
                 $taskInfo->name = $this->task->name;
                 $taskInfo->description = $this->task->description;
                 $taskInfo->orientation = $this->task->display;
                 $taskInfo->difficulty = $this->task->difficulty;
                 $taskInfo->min_class = $this->task->minClass;
                 $taskInfo->max_class = $this->task->maxClass;

                 $success = $taskInfo->save();
                 if (!$success) {
                     throw new InternalException(
                         "Could not insert taskInfo.",
                         context: ['taskInfo' => $taskInfo]
                     );
                 }
                 if ($this->task->tagIds) {
                     $taskInfo->tags()->attach($this->task->tagIds);
                 }
                 $taskInfoId = $taskInfo->id;
             }

             DebugLogger::log("Task info successfully inserted",['taskInfoId' => $taskInfoId]);
              // insert groups and resources
              {

                $insertGroupsBindings = [];
                foreach ($this->groupsAndResources as $groupAndResource) {
                    $group = &$groupAndResource[0];
                    $insertGroupsBindings[] = [$taskInfoId, $group->start, $group->length];
                }


                if ($insertGroupsBindings) {
                    $groupBindingsColumns = [
                        GroupConstants::COL_TASK_INFO_ID,
                        GroupConstants::COL_START,
                        GroupConstants::COL_LENGTH
                    ];
                    $groupIds = DBHelper::insertAndGetIds(
                        GroupConstants::TABLE_NAME,
                        GroupConstants::COL_ID,
                        columns: $groupBindingsColumns,
                        getIdsIfNotSupported:fn()=>array_values(
                            DB::table(GroupConstants::TABLE_NAME)
                        ->select(GroupConstants::COL_ID)
                        ->where(GroupConstants::COL_TASK_INFO_ID,'=',$taskInfoId)
                        ->pluck(GroupConstants::COL_ID)->all()
                    ),
                        values: $insertGroupsBindings,
                        unsetValuesArray: false
                    );
                    if(!$groupIds){
                        throw new InternalException("Could not insert groups.",[
                            'groupBindings'=>$insertGroupsBindings,
                            'groupBindingsColumns'=>$groupBindingsColumns
                        ]);
                    }
                    DebugLogger::log("Group ids",$groupIds);

                    // insert resources associated with groups
                    {
                        /**
                         * @var array<array<string,mixed>> $insertResourcesAssocData
                         */
                        $insertResourcesAssocData = [];
                        for ($i = 0; $i < count($groupIds); ++$i) {
                            $groupId = $groupIds[$i];
                            $resources = &$this->groupsAndResources[$i][1];
                            array_push(
                                $insertResourcesAssocData,
                                ...array_map(
                                    fn (BareResource $resource) => [
                                        ResourceConstants::COL_GROUP_ID => $groupId,
                                        ResourceConstants::COL_CONTENT => $resource->content
                                    ],
                                    $resources
                                )
                            );
                        }
                        if ($insertResourcesAssocData) {
                            DebugLogger::log("Resources",$insertResourcesAssocData);
                            $success = DB::table(ResourceConstants::TABLE_NAME)
                            ->insert($insertResourcesAssocData);
                            // /**
                            //  * @var bool $success
                            //  */
                            // $success = Resource::insert($insertResourcesAssocData);
                            if (!$success) {
                                throw new InternalException(
                                    message: "Could not insert resources.",
                                    context: [
                                        'resources' => $insertResourcesAssocData
                                    ]
                                );
                            }
                        }
                    }
                    DebugLogger::log("Resources were successfully inserted.");
                }
            }

            // insert exercises
            {
                $exerciseBindings = [];
                {
                foreach ($this->exerciseHelpers as $helperAndExercises) {
                    $exercises = $helperAndExercises[1];

                    foreach($exercises as $exercise){
                        $exerciseBindings[] = [
                            $taskInfoId,
                            $exercise->order,
                            $exercise->instructions,
                            $exercise->weight,
                            $exercise->exerciseType->value
                        ];
                    }
                }
            }
                    if ($exerciseBindings) {
                        $exerciseBindingsColumns = [
                            ExerciseConstants::COL_TASK_INFO_ID,
                            ExerciseConstants::COL_ORDER,
                            ExerciseConstants::COL_INSTRUCTIONS,
                            ExerciseConstants::COL_WEIGHT,
                            ExerciseConstants::COL_EXERCISEABLE_TYPE
                        ];
                        DebugLogger::log("Exercise bindings",$exerciseBindings);
                        $ids =  DBHelper::insertAndGetIds(
                            ExerciseConstants::TABLE_NAME,
                            ExerciseConstants::COL_ID,
                            columns: $exerciseBindingsColumns,
                            values: $exerciseBindings,
                            getIdsIfNotSupported:fn()=>
                            array_values(
                                DB::table(ExerciseConstants::TABLE_NAME)
                            ->select(ExerciseConstants::COL_ID)
                            ->where(ExerciseConstants::COL_TASK_INFO_ID,'=',$taskInfoId)
                            ->pluck(ExerciseConstants::COL_ID)
                            ->all()
                        ),
                            unsetValuesArray: false
                        );
                        if(!$ids){
                            throw new InternalException("Could not insert exercises",[
                                'exerciseBindings' =>$exerciseBindings,
                                'exerciseBindingsColumns' =>$exerciseBindingsColumns
                            ]);
                        }
                        foreach($this->exerciseHelpers as $helperAndExercises){
                            $helper = $helperAndExercises[0];
                            $exerciseCount = count($helperAndExercises[1]);
                            $helper->insertAll(array_slice($ids,0,$exerciseCount));
                            $helper->reset();
                            array_splice($ids,0,$exerciseCount);
                        }
                    }

            }
            return $taskInfoId;
        }

        /**
         * @throws \Throwable
         */
        public function insert(string $taskSource): int
        {
            $this->tryToGetHelper(addCurrentExercise: true);
            /**
             * @var int $taskId
             */
            $taskId = DB::transaction(function ()use($taskSource) {
               $taskInfoId = $this->insertTaskInfoAndContent();
                $taskId = null;
                // insert task
                {
                $task = new Task();
                //TODO: change line below to Auth::getUser()->id;
                $task->user_id = UserHelper::getUserId();
                $task->task_info_id = $taskInfoId;
                $task->is_public = $this->task->isPublic;
                $task->source = $taskSource;
                $task->saveOrFail();
                $taskId = $task->id;
                }
                DebugLogger::log("Task successfully inserted",['taskId' => $taskId]);

                return $taskId;
            });

            return $taskId;
        }

        /**
         * @throws \Throwable
         */
        public function update(int $taskId, string $taskSource){
            $this->tryToGetHelper(addCurrentExercise: true);
            DB::transaction(function () use($taskId,$taskSource) {
               $taskUpdateQuery = DB::table(TaskConstants::TABLE_NAME)
               ->where(TaskConstants::COL_ID,'=',$taskId);
                $taskUpdateData = [
                    TaskConstants::COL_IS_PUBLIC => $this->task->isPublic,
                    TaskConstants::COL_VERSION => DB::raw(TaskConstants::COL_VERSION." + 1"),
                    TaskConstants::COL_SOURCE => $taskSource
                ];

                DebugLogger::log("Task successfully inserted",['taskId' => $taskId]);

               $taskInfoId = DB::table(TaskConstants::TABLE_NAME)
                ->select([TaskConstants::COL_TASK_INFO_ID])
                ->where(TaskConstants::COL_ID,'=',$taskId)
                ->lockForUpdate()
                ->value(TaskConstants::COL_TASK_INFO_ID);

               $reviewTemplateExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID,'=',$taskInfoId)
                ->exists();
                if($reviewTemplateExists){
                   TaskHelper::deleteActualExercisesByTaskInfo($taskInfoId);
                }
                else{
                        DB::table(GroupConstants::TABLE_NAME)
                            ->where(GroupConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                            ->delete();
                        // Resources should be deleted by cascade, so we do not need to delete them here
                        DB::table(ExerciseConstants::TABLE_NAME)
                            ->where(ExerciseConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                            ->delete();
                        // We do not need to delete actual exercises, beacuse this should be done by delete cascade
                        DB::table(TagTaskInfoConstants::TABLE_NAME)
                            ->where(TagTaskInfoConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                            ->delete();
                    }

                // Here we are inserting new task info (by passing null as id) if review template exists,
                // otherwise we will just update existing one
                $newTaskInfoId = $this->insertTaskInfoAndContent($reviewTemplateExists ? null : $taskInfoId);
                $taskUpdateData[TaskConstants::COL_TASK_INFO_ID]=$newTaskInfoId;
                $updated = $taskUpdateQuery->update($taskUpdateData);
                if($updated !== 1){
                 throw new InternalException("Could not update task with id '$taskId'.",
                 context:[
                     'taskData'=>$taskUpdateData,
                     'taskId'=>$taskId,
                     'updated'=>$updated
                 ]);
                }
            });

        }
    }
}
