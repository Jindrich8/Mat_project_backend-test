<?php

namespace App\Models;

use App\Helpers\ExerciseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exercise extends BaseModel
{
    use HasFactory;

    public static function getExerciseType(Exercise $exercise): ExerciseType
    {
        return ExerciseType::from($exercise->exerciseable_type);
    }
}
