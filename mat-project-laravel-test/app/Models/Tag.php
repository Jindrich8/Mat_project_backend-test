<?php

namespace App\Models;

use App\ModelConstants\TagTaskInfoConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends BaseModel
{
    use HasFactory;
    public const NAME = 'name';

    public function tasks():BelongsToMany{
        return $this->belongsToMany(TaskInfo::class,table:TagTaskInfoConstants::TABLE_NAME);
    }
}
