<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;

// use Mchuluq\Larv\Rbac\Models\User as User

class RememberToken extends Model
{
    protected $fillable = [
        'token', 'user_id', 'expires_at','user_agent','ip_address'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
