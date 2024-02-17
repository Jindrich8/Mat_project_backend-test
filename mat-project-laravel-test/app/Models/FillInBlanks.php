<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FillInBlanks extends BaseModel
{
    use HasFactory;

    public $primaryKey = 'exerciseable_id';
}
