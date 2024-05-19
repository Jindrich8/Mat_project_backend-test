<?php

namespace App\MyConfigs {

    use App\Helpers\ExerciseType;
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

        public function getFillInBlanksConfig(): FillInBlanks\Config
        {
            return $this->fillInBlanksConfig;
        }

        public function getFixErrorsConfig(): FixErrors\Config
        {
            return $this->fixErrorsConfig;
        }


        public function __construct(string $lang = 'cs')
        {

            $this->maxGroupDepth = 3;
            $this->maxExerciseCount = 50;
            $this->maxResourceCountInResources = 3;



            switch ($lang) {
                case 'en': {
                        $this->taskName = 'document';

                        $this->taskDescription = new ValidableString('description', 255, 1);
                        $this->taskContentName = 'entries';

                        $this->groupName = 'group';
                        $this->groupMembersName = 'members';
                        $this->groupResourcesName = 'resources';

                        $this->resourcesResource = new ValidableString('resource', 2000, 1);

                        $this->exerciseName = 'exercise';
                        $this->exerciseInstructions = new ValidableString('instructions', 255, 1);
                        $this->exerciseTypeAttr = new ValidableEnum(
                            'type',
                            [],
                            ExerciseType::FillInBlanks,
                            flags: ValidableEnumFlags::ALLOW_ENUM_VALUES
                        );
                        $this->exerciseWeightAttr = new ValidableInt('weight', 100, 1);

                        $this->exerciseContentName = 'content';

                        $this->fillInBlanksConfig = new FillInBlanks\Config(
                            uiCmpStart: '[',
                            uiCmpEnd: ']',
                            cmbValuesSep: '/',
                            escape: "\\"
                        );

                        $this->fixErrorsConfig = new FixErrors\Config(
                            correctText: new ValidableString('correctText', maxLen: 1024, minLen: 1),
                            wrongText: new ValidableString('text', maxLen: 1024, minLen: 1)
                        );
                    }
                    break;
                case 'cs': {
                        $this->taskName = 'dokument';

                        $this->taskDescription = new ValidableString('popis', 255, 1);
                        $this->taskContentName = 'polozky';

                        $this->groupName = 'skupina';
                        $this->groupMembersName = 'clenove';
                        $this->groupResourcesName = 'zdroje';

                        $this->resourcesResource = new ValidableString('zdroj', 2000, 1);

                        $this->exerciseName = 'cviceni';
                        $this->exerciseInstructions = new ValidableString('instrukce', 255, 1);
                        $this->exerciseTypeAttr = new ValidableEnum(
                            'typ',
                            [
                                'Doplnovacka' => ExerciseType::FillInBlanks,
                                'OpravovaniChyb' => ExerciseType::FixErrors
                            ],
                            ExerciseType::FillInBlanks
                        );
                        $this->exerciseWeightAttr = new ValidableInt('vaha', 100, 1);

                        $this->exerciseContentName = 'obsah';

                        $this->fillInBlanksConfig = new FillInBlanks\Config(
                            uiCmpStart: '[',
                            uiCmpEnd: ']',
                            cmbValuesSep: '/',
                            escape: "\\"
                        );

                        $this->fixErrorsConfig = new FixErrors\Config(
                            correctText: new ValidableString('spravnyText', maxLen: 1024, minLen: 1),
                            wrongText: new ValidableString('text', maxLen: 1024, minLen: 1)
                        );
                    }
                    break;
            }
        }

        private static ?self $instance = null;

        public static function get(): self
        {
            return (self::$instance ??= new self());
        }
    }
}
