<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\InternalException;
    use App\Helper\Database\PgDB;
    use App\Helpers\CCreateExerciseHelper;
    use App\Helpers\ExerciseHelper;
    use App\Helpers\ExerciseType;
    use App\Models\Exercise;
    use App\Helpers\BareModels\BareExercise;
    use App\Models\Group;
    use App\Helpers\BareModels\BareGroup;
    use App\Models\Task;
    use App\Types\CCreateExerciseHelperState;
    use App\Utils\Utils;
    use App\Helpers\BareModels\BareResource;
    use App\Models\Resource;
    use App\Models\Tag;
    use App\Types\XMLDynamicNodeBase;
    use App\Types\XMLNodeBase;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use DB;

    class TaskRes
    {
        public ?Task $task;

        /**
         * @var int[] $tagsIds
         */
        public array $tagsIds;
        /**
         * @var array<array{BareGroup,array<BareResource>}> $groupsAndResources
         */
        private array $groupsAndResources;

        private ?int $currentGroupIndex;

        private ?BareExercise $currentExercise;

        private int $exerciseCount = 0;

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
            $this->tagsIds = [];
        }


        /**
         * Returns previous group index
         */
        public function addGroup(): ?int
        {
            echo "\nAdding new group: ";
            $preGroupIndex = $this->currentGroupIndex;
            $this->currentGroupIndex = count($this->groupsAndResources);
            $group = new BareGroup();
            $group->start = $this->getExerciseCount();
            $this->groupsAndResources[] = [$group, []];
            dump($this->groupsAndResources);
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
            };
        }

        public function addResourceToCurrentGroup(): void
        {
            echo "\nAdding resource to current group\n";
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
                    case CCreateExerciseHelperState::EXERCISE_ENDED:
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
            $this->currentExercise = new BareExercise();
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
                    $this->exerciseHelpers[$type->name] = [ExerciseHelper::getHelper($type)->getCreateHelper(), []];
                }
                if ($addCurrentExercise) {
                    $helperAndExercises[1][] = $this->currentExercise;
                }
                $helper = $helperAndExercises[0];
            }
            return $helper;
        }

        public function insert()
        {
            $this->tryToGetHelper(addCurrentExercise: true);
            DB::transaction(function () {
                // insert task and tags
                {
                    if($this->tagsIds){
                    $this->task->tags()->attach($this->tagsIds);
                    }
                    $success = $this->task->save();
                    if (!$success) {
                        throw new InternalException(
                            "Could not insert task and its tags.",
                        context:['tags'=>$this->tagsIds,'task'=>$this->task]
                        );
                    }
                }

                $taskId = $this->task->id;
                // insert groups and resources
                {
                    $insertGroupsBindings = [];
                    foreach ($this->groupsAndResources as $groupAndResource) {
                        $group = &$groupAndResource[0];
                        $insertGroupsBindings[] = [$taskId, $group->start, $group->length];
                    }


                    if ($insertGroupsBindings) {
                        $groupIds = PgDB::insertAndGetIds(
                            Group::getTableName(),
                            Group::getPrimaryKeyName(),
                            columns: [Group::TASK_ID, Group::START, Group::LENGTH],
                            values: $insertGroupsBindings,
                            unsetValuesArray: true
                        );

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
                                    array_map(
                                        fn (BareResource $resource) => [
                                            Resource::GROUP_ID => $groupId,
                                            Resource::CONTENT => $resource->content
                                        ],
                                        $resources
                                    )
                                );
                            }
                            if ($insertResourcesAssocData) {
                                /**
                                 * @var bool $success
                                 */
                                $success = Resource::insert($insertResourcesAssocData);
                                if (!$success) {
                                    // TODO: implement
                                    throw new InternalException(
                                        message:"Could not insert resources.",
                                        context:[
                                            'resources'=>$insertResourcesAssocData
                                        ]
                                        );
                                }
                            }
                        }
                    }
                }

                // insert exercises
                {
                    foreach ($this->exerciseHelpers as $exercisesAndHelper) {
                        $exercises = $exercisesAndHelper[1];
                        $helper = $exercisesAndHelper[0];

                        $exerciseBindings = [];
                        $exercisesCount = count($exercises);
                        for ($i = 0; $i < $exercisesCount; ++$i) {
                            $exercise = $exercises[$i];
                            $exerciseBindings[] = [
                                $taskId,
                                $i,
                                $exercise->instructions,
                                $exercise->weight,
                                $exercise->exerciseType->value
                            ];
                        }
                        if ($exerciseBindings) {
                            $ids =  PgDB::insertAndGetIds(
                                Exercise::getTableName(),
                                Exercise::getPrimaryKeyName(),
                                columns: [Exercise::TASK_ID, Exercise::ORDER, Exercise::INSTRUCTIONS, Exercise::WEIGHT, Exercise::EXERCISEABLE_TYPE],
                                values: $exerciseBindings,
                                unsetValuesArray: true
                            );
                            $helper->insertAll($ids);
                        }
                    }
                }
            });
        }
    }
}
