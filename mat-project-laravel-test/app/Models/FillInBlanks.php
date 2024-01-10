<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FillInBlanks extends BaseModel
{
    use HasFactory;

    const CONTENT = 'content';
    const ID = 'exerciseable_id';
}
