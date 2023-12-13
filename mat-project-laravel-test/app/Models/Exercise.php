<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
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

}
