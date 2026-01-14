<?php namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;

class RoleActor extends Model{

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array('role_id','group_id','account_id');

    function assign($for, $role_id, $type = 'account_id'){
        $data = array();
        if (!$role_id) {
            return;
        }
        if (in_array($type, ['account_id', 'group_id'])) {
            if (is_array($role_id)) {
                foreach ($role_id as $key => $r) {
                    $data[$key]['role_id'] = $r;
                    $data[$key][$type] = $for;
                }
            } else {
                $data[0]['role_id'] = $role_id;
                $data[0][$type] = $for;
            }
        }
        return $this->insert($data);
    }

    function remove($for, $type = 'account_id'){
        if (in_array($type, ['account_id', 'group_id'])) {
            $this->where([$type => $for])->delete();
        }
    }

    function getFor($for, $type){
        $result = [];
        $get = $this->where([$type => $for])->get();
        foreach ($get as $role) {
            $result[] = $role->role_id;
        }
        return $result;
    }

    public function group(){
        return $this->hasOne(Group::class,'id','group_id');
    }
    public function account(){
        return $this->hasOne(Account::class,'id','account_id');
    }
}
