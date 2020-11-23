<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Support\Str;

// use Mchuluq\Larv\Rbac\Models\RememberToken as RememberToken
// use Mchuluq\Larv\Rbac\Models\Account as Account

class User extends Authenticatable{

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'avatar_url','email_verified_at','password','active','account_id'
    ];
    
    use BaseAuthenticatable;

    protected static function boot(){
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function rememberTokens(){
        return $this->hasMany(RememberToken::class);
    }
    
    public function accounts(){
        return $this->hasMany(Account::class);
    }
}
