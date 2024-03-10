<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends BaseModel
{
    use HasFactory;

    public const CONTENT ='content';
    public const GROUP_ID = 'group_id';

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
