<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Mchuluq\Larv\Rbac\Traits\HasPermission;
use Mchuluq\Larv\Rbac\Traits\HasRole;
use Mchuluq\Larv\Rbac\Traits\HasDataAccess;

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
        static::saved(function($model){
            if($model->active == true){
                $model->user->update(['account_id'=>$model->id]);
            }
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
