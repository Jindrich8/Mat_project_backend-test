<?php

namespace App\Type {

    use App\TableSpecificData\TaskClass;
    use App\TableSpecificData\TaskDifficulty;
    use App\TableSpecificData\TaskDisplay;

    class TaskResTask
    {
        public ?string $name = null;
        public ?string $description = null;
        public ?TaskDisplay $display = null;
        public ?TaskDifficulty $difficulty = null;
        public ?TaskClass $minClass = null;
        public ?TaskClass $maxClass = null;
        public ?bool $isPublic = null;
        /**
         * @var int[] $tagIds
         */
        public array $tagIds = [];
    }
}