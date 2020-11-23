<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// use Mchuluq\Larv\Rbac\Models\User as User

class Account extends Model{

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
            $model->default_url = route('rbac.account.switch', ['account_id' => $model->id,'default'=>'default']);
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function accountable(){
        return $this->morphTo();
    }
}
