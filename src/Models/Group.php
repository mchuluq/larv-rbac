<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Mchuluq\Larv\Rbac\Traits\HasPermission;
use Mchuluq\Larv\Rbac\Traits\HasRole;

// use Mchuluq\Larv\Rbac\Models\Account as Account

class Group extends Model{

    use HasRole,HasPermission;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name','image','description'
    ];

    public $timestamps = false;

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::slug($model->name,'-');
        });
    }

    public function accounts(){
        return $this->hasMany(Account::class);
    }

}
