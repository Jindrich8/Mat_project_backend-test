<?php

namespace App\Models;

use App\ModelConstants\TagTaskConstants;
use App\ModelConstants\TagTaskInfoConstants;
use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskInfo extends BaseModel
{
    use HasFactory;

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
        return $this->belongsToMany(Tag::class,table:TagTaskInfoConstants::TABLE_NAME);
    }
}
