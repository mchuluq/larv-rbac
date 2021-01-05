<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

Builder::macro('filterDataAccess', function ($column_name,$data_type,$skip_null=false) {
    $data_access = session()->get('rbac.data_access', null);
    $data_ids = Arr::get($data_access,$data_type,[]);
    if((!Auth::check() && $skip_null) || (empty($data_ids) && $skip_null)){
        return $this;
    }
    if(is_array($data_ids)){
        $this->whereIn($column_name, $data_ids);
    }else{
        $this->where($column_name, $data_ids);
    }
    return $this;
});
