<?php

namespace App\MyConfigs {

    class TaskSrcConfig
    {
        public readonly int $maxGroupDepth;
        public readonly int $maxExerciseCount;
        public readonly int $maxResourceCountInGroup;

        

        public readonly string $taskName;
        public readonly string $taskNameName;
        public readonly string $taskOrientationName;

        public readonly string $taskDescriptionName;
        public readonly string $taskContentName;
        
        
        public readonly string $groupName;

        public readonly string $groupMembersName;
        public readonly string $groupResourcesName;

        public readonly string $resourcesResourceName;
        

        public readonly string $exerciseName;
        public readonly string $exerciseInstructionsName;
        public readonly string $exerciseTypeName;
        public readonly string $exerciseWeightName;

        public readonly string $exerciseContentName;


        public function __construct(string $lang = 'en'){

        }
    }
}