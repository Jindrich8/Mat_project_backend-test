<?php

namespace App\Models;

use App\TableSpecificData\TaskDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends BaseModel
{
    use HasFactory;

    public static function getOrientation(Task $task):TaskDisplay{
        return TaskDisplay::from($task->orientation);
    }

    public function tags(){
        return $this->belongsToMany(Tag::class);
    }
}