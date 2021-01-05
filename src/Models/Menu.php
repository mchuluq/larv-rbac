<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
class Menu extends Model{

    protected $primaryKey = 'id';
    protected $fillable = [
        // 'route','label','html_attr','icon','parent_id','position','is_visible','quick_access','display_order','description'
        'route','label','html_attr','icon','group','position','display_order','description'
    ];
    protected $attributes = [
        'display_order' => 0,
    ];

    public $timestamps = false;
}
