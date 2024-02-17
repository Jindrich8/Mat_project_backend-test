<?php

namespace App\MyConfigs {

    use App\Helpers\ExerciseType;
    use App\TableSpecificData\TaskDisplay;
    use App\Types\ValidableEnum;
    use App\Types\ValidableEnumFlags;
    use App\Types\ValidableInt;
    use App\Types\ValidableString;
    use App\Helpers\Exercises\FillInBlanks;
    use App\Helpers\Exercises\FixErrors;

    class TaskSrcConfig
    {
        public readonly int $maxGroupDepth;
        public readonly int $maxExerciseCount;
        public readonly int $maxResourceCountInResources;

        

        
        public readonly string $taskName;
        public readonly ValidableString $taskNameAttr;
        /**
         * @var ValidableEnum<TaskDisplay> $taskOrientationAttr
         */
        public readonly ValidableEnum $taskOrientationAttr;

        public readonly ValidableString $taskDescription;
        public readonly string $taskContentName;
        
        
        public readonly string $groupName;

        public readonly string $groupMembersName;
        public readonly string $groupResourcesName;

        public readonly ValidableString $resourcesResource;
        

        public readonly string $exerciseName;
        public readonly ValidableString $exerciseInstructions;
        public readonly ValidableEnum $exerciseTypeAttr;
        public readonly ValidableInt $exerciseWeightAttr;

        public readonly string $exerciseContentName;

        private readonly FillInBlanks\Config $fillInBlanksConfig;
        private readonly FixErrors\Config $fixErrorsConfig;

        public function getFillInBlanksConfig():FillInBlanks\Config{
            return $this->fillInBlanksConfig;
        }

        public function getFixErrorsConfig():FixErrors\Config{
            return $this->fixErrorsConfig;
        }


        public function __construct(string $lang = 'en'){

            $this->maxGroupDepth = 3;
            $this->maxExerciseCount = 50;
            $this->maxResourceCountInResources = 3;
    
            
    
            
            $this->taskName = 'document';
            $this->taskNameAttr = new ValidableString('name',50,5);
            $this->taskOrientationAttr = new ValidableEnum('orientation',[],TaskDisplay::HORIZONTAL,
            flags:ValidableEnumFlags::ALLOW_ENUM_VALUES);
    
            $this->taskDescription = new ValidableString('description',255,1);
            $this->taskContentName = 'content';
            
            
            $this->groupName = 'group';
    
            $this->groupMembersName = 'members';
            $this->groupResourcesName = 'resources';
    
            $this->resourcesResource = new ValidableString('resource',1000,1);
            
    
            $this->exerciseName = 'exercise';
            $this->exerciseInstructions = new ValidableString('instructions',255,1);
            $this->exerciseTypeAttr = new ValidableEnum('type',[],ExerciseType::FillInBlanks,
             flags:ValidableEnumFlags::ALLOW_ENUM_VALUES);
            $this->exerciseWeightAttr = new ValidableInt('weight',100,1);
    
            $this->exerciseContentName = 'content';

            $this->fillInBlanksConfig = new FillInBlanks\Config(
                uiCmpStart:'[',
                uiCmpEnd:']',
                cmbValuesSep:'/',
                escape:"\\"
            );

            $this->fixErrorsConfig = new FixErrors\Config(
                correctTextName:'correctText',
                wrongTextName:'text'
            );
        }

        public static function get():self{
            return new self();
        }
    }
}