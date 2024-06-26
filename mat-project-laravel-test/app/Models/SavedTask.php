<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavedTask extends BaseModel
{
    use HasFactory;

    public const DATA = 'data';
    public const USER_ID = 'user_id';
    public const TASK_ID = 'task_id';
    public const TASK_VERSION = 'task_version';
}
