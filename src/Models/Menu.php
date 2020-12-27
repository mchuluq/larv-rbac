<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Kalnoy\Nestedset\NodeTrait;

class Menu extends Model{

    use NodeTrait;
    protected $primaryKey = 'id';
    protected $fillable = [
        'route','label','html_attr','icon','parent_id','position','is_visible','quick_access','display_order','description'
    ];
    protected $attributes = [
        'quick_access' => 0,
        'display_order' => 0,
        'is_visible' => true
    ];

    public $timestamps = false;
}
