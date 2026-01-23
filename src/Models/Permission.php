<?php namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model{

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array('route','role_id','group_id','account_id');

    function assign($for, $route, $type = 'account_id'){
        $data = array();
        if (!$route) {
            return $this->remove($for, $type);
        }
        if (in_array($type, ['account_id', 'group_id','role_id'])) {
            if (is_array($route)) {
                foreach ($route as $key => $m) {
                    $data[$key]['route'] = $m;
                    $data[$key][$type] = $for;
                }
            } else {
                $data[0]['route'] = $route;
                $data[0][$type] = $for;
            }
        }
        return $this->insert($data);
    }

    function remove($for, $type = 'account_id'){
        if (in_array($type, ['role_id','account_id', 'group_id'])) {
            $this->where([$type => $for])->delete();
        }
    }

    function getFor($for, $type){
        $result = [];
        $get = $this->where([$type => $for])->get();
        foreach ($get as $perm) {
            $result[] = $perm->route;
        }
        return $result;
    }

    public function group(){
        return $this->hasOne(Group::class,'id','group_id');
    }
    public function account(){
        return $this->hasOne(Account::class,'id','account_id');
    }
    public function role(){
        return $this->hasOne(Role::class,'id','role_id');
    }
}
