<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    private static ?string $staticTableName = null;
    private static ?string $staticPrimaryKeyName = null;

    public static function getTableName():string{
        if(!self::$staticTableName){
            self::storeModelInfo();
        }
        return self::$staticTableName;
    }

    public static function getPrimaryKeyName():string{
        if(!self::$staticPrimaryKeyName){
            self::storeModelInfo();
        }
        return self::$staticPrimaryKeyName;
    }

    private static function storeModelInfo(){
        $user = new static();
        self::$staticTableName = $user->table;
        self::$staticPrimaryKeyName = $user->primaryKey;
    }
    

    const ID = 'id';
    const TABLE_NAME = 'users';
    const NAME = 'name';
    private static ?string $staticTableName = null;
    private static ?string $staticPrimaryKeyName = null;

    public static function getTableName():string{
        if(!self::$staticTableName){
            self::storeModelInfo();
        }
        return self::$staticTableName;
    }

    public static function getPrimaryKeyName():string{
        if(!self::$staticPrimaryKeyName){
            self::storeModelInfo();
        }
        return self::$staticPrimaryKeyName;
    }

    private static function storeModelInfo(){
        $user = new static();
        self::$staticTableName = $user->table;
        self::$staticPrimaryKeyName = $user->primaryKey;
    }
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
