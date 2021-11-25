<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

Builder::macro('filterDataAccessConfig', function (array $config,$column_name,$data_type,$skip_null=false) {
    $data_access = session()->get('rbac.data_access', null);
    $data_ids = Arr::get($data_access,$data_type,[]);
    
    if((!Auth::check() && $skip_null) || (empty($data_ids) && $skip_null)){
        return $this;
    }
    
    $filter = [];
    foreach($config as $key=>$arrs){
        foreach($data_ids as $id){
            if(in_array($id,$arrs)){
                $filter[] = $key;
            }
        }
    }
    $filter = array_unique($filter);
    
    if(is_array($filter)){
        $this->whereIn($column_name, $filter);
    }else{
        $this->where($column_name, $filter);
    }
    return $this;
});
