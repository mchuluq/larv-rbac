<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Kalnoy\Nestedset\NodeTrait;

class Menu extends Model{

    use NodeTrait;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'route','label','html_attr','icon','parent_id','position','is_visible','quick_access','display_order','description'
    ];

    public $timestamps = false;

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::slug($model->label,'-');
        });
    }
}
