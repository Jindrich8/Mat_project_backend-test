<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends BaseModel
{
    use HasFactory;


    public const START = 'start';
    public const LENGTH = 'length';
    public const TASK_ID = 'task_id';


    public function __construct(array $attributes = []){
        $this->table = self::getTableName();
        parent::__construct($attributes);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
