<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagTask extends BaseModel
{
    use HasFactory;

    const TASK_ID = 'task_id';
    const TAG_ID = 'tag_id';
}
