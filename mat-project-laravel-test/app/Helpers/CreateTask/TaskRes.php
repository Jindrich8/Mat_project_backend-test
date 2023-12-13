<?php

namespace App\Helpers\CreateTask {

    use App\Models\Task;

    class TaskRes
    {
        public Task $task;
        public array $groups;
        public array $exercises;
        
    }
}