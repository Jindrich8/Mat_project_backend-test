<?php

namespace App\Models;

use App\Helpers\ExerciseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exercise extends BaseModel
{
    use HasFactory;

const ID = 'id';
const ORDER = 'order';
const WEIGHT = 'weight';
const EXERCISEABLE_TYPE='exerciseable_type';

const INSTRUCTIONS = 'instructions';
const TASK_ID = 'task_id';
const CREATED_AT='created_at';
const UPDATED_AT = 'updated_at';

public static function getExerciseType(Exercise $exercise):ExerciseType{
    return ExerciseType::from($exercise->exerciseable_type);
}

}
