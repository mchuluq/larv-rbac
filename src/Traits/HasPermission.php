<?php

namespace Mchuluq\Larv\Rbac\Traits;

use Mchuluq\Larv\Rbac\Models\Account;
use Mchuluq\Larv\Rbac\Models\Group;
use Mchuluq\Larv\Rbac\Models\Role;
use Mchuluq\Larv\Rbac\Models\Permission;


trait HasPermission {

    function permissions(){
        if ($this instanceof Account) {
            return $this->hasMany(Permission::class, 'account_id', 'id');
        } elseif ($this instanceof Group) {
            return $this->hasMany(Permission::class, 'group_id', 'id');
        } elseif ($this instanceof Role) {
            return $this->hasMany(Permission::class, 'role_id', 'id');
        }
        return;
    }

    function assignPermissions($menu_id){
        $permissions = new Permission();
        $this->removePermissions();
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $permissions->assign($this->id, $menu_id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $permissions->assign($this->id, $menu_id, 'group_id') : false;
        } elseif ($this instanceof Role) {
            return (!is_null($this->id)) ? $permissions->assign($this->id, $menu_id, 'role_id') : false;
        }
        return;
    }

    function removePermissions(){
        $permissions = new Permission();
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $permissions->remove($this->id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $permissions->remove($this->id, 'group_id') : false;
        } elseif ($this instanceof Role) {
            return (!is_null($this->id)) ? $permissions->remove($this->id, 'role_id') : false;
        }
        return;
    }

    function getPermissions(){
        $permissions = new Permission();
        if ($this instanceof Account) {
            $this->attributes['permissions'] = (!is_null($this->id)) ? $permissions->getFor($this->id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            $this->attributes['permissions'] = (!is_null($this->id)) ? $permissions->getFor($this->id, 'group_id') : false;
        } elseif ($this instanceof Role) {
            $this->attributes['permissions'] = (!is_null($this->id)) ? $permissions->getFor($this->id, 'role_id') : false;
        }
        return $this;
    }

    function isHasPermission($route){
        if (!isset($this->attributes['permissions'])) {
            return false;
        }
        return in_array($route, $this->attributes['permissions']);
    }
    

}