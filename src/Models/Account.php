<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Mchuluq\Larv\Rbac\Traits\HasPermission;
use Mchuluq\Larv\Rbac\Traits\HasRole;
use Mchuluq\Larv\Rbac\Traits\HasDataAccess;

// use Mchuluq\Larv\Rbac\Models\User as User
// use Mchuluq\Larv\Rbac\Models\Group as Group

class Account extends Model{

    use HasPermission,HasRole,HasDataAccess;

    protected $fillable = [
        'user_id', 'group_id', 'active','accountable_id','accountable_type'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public $timestamps = false;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
        static::retrieved(function($model){
            $model->url = route('rbac.account.switch', ['account_id' => $model->id]);
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function accountable(){
        return $this->morphTo();
    }
}
