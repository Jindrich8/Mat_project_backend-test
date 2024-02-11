<?php

namespace App\Models;

use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskInfo extends BaseModel
{
    use HasFactory;

    const NAME = 'name';
    const DIFFICULTY = 'difficulty';
    const DESCRIPTION = 'description';
    const MIN_CLASS = 'min_class';
    const MAX_CLASS = 'max_class';
    const USER_ID = 'user_id';
    const IS_PUBLIC = 'is_public';
    const VERSION = 'version';
    const ORIENTATION = 'orientation';
    


    public static function getMaxClass(TaskInfo $task){
        return TaskClass::from($task->max_class);
    }

    public static function getMinClass(TaskInfo $task){
        return TaskClass::from($task->min_class);
    }

    public static function getDifficulty(TaskInfo $task){
        return TaskDifficulty::from($task->difficulty);
    }

    public static function getOrientation(TaskInfo $task){
        return TaskDisplay::from($task->orientation);
    }

    public function tags(){
        return $this->belongsToMany(Tag::class);
    }
}