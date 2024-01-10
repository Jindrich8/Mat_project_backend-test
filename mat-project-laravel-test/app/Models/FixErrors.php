<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixErrors extends BaseModel
{
    use HasFactory;

    const ID = 'exerciseable_id';
    const CORRECT_TEXT = 'correct_text';
    const WRONG_TEXT = 'wrong_text';
}
