<?php

namespace Mchuluq\Larv\Rbac\Traits;

use Mchuluq\Larv\Rbac\Models\Account;
use Mchuluq\Larv\Rbac\Models\Group;
use Mchuluq\Larv\Rbac\Models\Role;
use Mchuluq\Larv\Rbac\Models\DataAccess;


trait HasDataAccess {

    function dataAccess(){
        if ($this instanceof Account) {
            return $this->hasMany(DataAccess::class, 'account_id', 'id');
        } elseif ($this instanceof Group) {
            return $this->hasMany(DataAccess::class, 'group_id', 'id');
        } elseif ($this instanceof Role) {
            return $this->hasMany(DataAccess::class, 'role_id', 'id');
        }
        return;
    }

    function assignDataAccess($data_id,$data_type){
        $dataaccess = new DataACcess();
        $this->removeDataAccess($data_type);
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $dataaccess->assign($this->id, $data_id,$data_type, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $dataaccess->assign($this->id, $data_id,$data_type, 'group_id') : false;
        } elseif ($this instanceof Role) {
            return (!is_null($this->id)) ? $dataaccess->assign($this->id, $data_id,$data_type, 'role_id') : false;
        }
        return;
    }

    function removeDataAccess($data_type=null){
        $dataaccess = new DataAccess();
        if ($this instanceof Account) {
            return (!is_null($this->id)) ? $dataaccess->remove($this->id,$data_type, 'account_id') : false;
        } elseif ($this instanceof Group) {
            return (!is_null($this->id)) ? $dataaccess->remove($this->id,$data_type, 'group_id') : false;
        } elseif ($this instanceof Role) {
            return (!is_null($this->id)) ? $dataaccess->remove($this->id,$data_type, 'role_id') : false;
        }
        return;
    }

    function getDataAccess(){
        $dataaccess = new DataAccess();
        if ($this instanceof Account) {
            $this->attributes['data_access'] = (!is_null($this->id)) ? $dataaccess->getFor($this->id, 'account_id') : false;
        } elseif ($this instanceof Group) {
            $this->attributes['data_access'] = (!is_null($this->id)) ? $dataaccess->getFor($this->id, 'group_id') : false;
        } elseif ($this instanceof Role) {
            $this->attributes['data_access'] = (!is_null($this->id)) ? $dataaccess->getFor($this->id, 'role_id') : false;
        }
        return $this;
    }

    function isHasDataAccess($data_id,$data_type){
        if (!isset($this->attributes['permissions'])) {
            return false;
        }
        return in_array($data_id, $this->attributes['permissions'][$data_type]);
    }
    

}