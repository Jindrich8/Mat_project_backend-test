<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends BaseModel
{
    use HasFactory;


    public const START = 'start';
    public const LENGTH = 'length';
    public const TASK_ID = 'task_id';

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
