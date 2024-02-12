<?php

namespace App\Models;

use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends BaseModel
{
    use HasFactory;

    public static function getMaxClass(Task $task){
        return TaskClass::from($task->max_class);
    }

    public static function getMinClass(Task $task){
        return TaskClass::from($task->min_class);
    }

    public static function getDifficulty(Task $task){
        return TaskDifficulty::from($task->difficulty);
    }

    public static function getOrientation(Task $task){
        return TaskDisplay::from($task->orientation);
    }

    public function tags(){
        return $this->belongsToMany(Tag::class);
    }
}