<?php

namespace Mchuluq\Larv\Rbac\Traits;

use Mchuluq\Larv\Rbac\Models\Account;
use Mchuluq\Larv\Rbac\Models\Group;
use Mchuluq\Larv\Rbac\Models\RoleActor;


trait HasRole {

    function roles(){
        if ($this instanceof Account) {
            return $this->hasMany(RoleActor::class, 'account_id', 'id');
        } elseif ($this instanceof Group) {
            return $this->hasMany(RoleActor::class, 'group_id', 'id');
        }
        return;
    }

    function assignRoles($role_id){
        $role_actors = new RoleActor();
        $this->removeRoles();
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $role_actors->assign($this->id, $role_id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $role_actors->assign($this->id, $role_id, 'group_id') : false;
        }
        return;
    }

    function removeRoles(){
        $role_actors = new RoleActor();
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $role_actors->remove($this->id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $role_actors->remove($this->id, 'group_id') : false;
        }
        return;
    }

    function getRoles(){
        $role_actors = new RoleActor();
        if ($this instanceof Account) {
            $this->attributes['roles'] = (!is_null($this->id)) ? $role_actors->getFor($this->id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            $this->attributes['roles'] = (!is_null($this->id)) ? $role_actors->getFor($this->id, 'group_id') : false;
        }
        return $this;
    }

    function isHasRole($role_id){
        if (!isset($this->attributes['roles'])) {
            return false;
        }
        return in_array($role_id, $this->attributes['roles']);
    }
    

}