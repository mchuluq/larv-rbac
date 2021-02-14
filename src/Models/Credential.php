<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Credential extends Model{

    use HasFactory;

    protected $fillable = [
        'user_id','credId','key',
    ];
}
