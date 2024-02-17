<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixErrors extends BaseModel
{
    use HasFactory;

    public $primaryKey = 'exerciseable_id';
}
