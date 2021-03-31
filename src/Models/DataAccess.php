<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;

class DataAccess extends Model{

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array('data_type','data_id','role_id','group_id','account_id');

    function assign($for, $data_id, $data_type, $type = 'account_id'){
        $data = array();
        if (!$data_id || !$data_type) {
            return;
        }
        if (in_array($type, ['account_id', 'group_id','role_id'])) {
            if (is_array($data_id)) {
                foreach ($data_id as $key => $d) {
                    $data[$key]['data_id'] = $d;
                    $data[$key]['data_type'] = $data_type;
                    $data[$key][$type] = $for;
                }
            } else {
                $data[0]['data_id'] = $data_id;
                $data[0]['data_type'] = $data_type;
                $data[0][$type] = $for;
            }
        }
        return $this->insert($data);
    }

    function remove($for,$data_type, $type = 'account_id'){
        if (in_array($type, ['role_id','account_id', 'group_id'])) {
            if(!$data_type){
                $this->where([$type => $for])->delete();
            }else{
                $this->where(['data_type' => $data_type, $type => $for])->delete();
            }
        }
    }

    function getFor($for, $type){
        $access_types = config('rbac.access_type',[]);
        $result = [];
        foreach($access_types as $access_type){
            $result[$access_type] = array();
        }
        $get = $this->where([$type => $for])->get();
        foreach ($get as $dt) {
            $result[$dt->data_type][] = $dt->data_id;
        }
        return $result;
    }
}
