<?php namespace Mchuluq\Larv\Rbac\Traits;

use Carbon\Carbon;

use Mchuluq\Larv\Rbac\Models\Account;
use Mchuluq\Larv\Rbac\Models\RememberToken;
use Mchuluq\Larv\Rbac\Helpers\ModelStorage;
use Mchuluq\Larv\Rbac\Models\Session;

trait HasRbac {

    public function sessions(){
        return $this->hasMany(Session::class);
    }

    public function rememberTokens(){
        return $this->hasMany(RememberToken::class);
    }
    public function getRememberToken(){
        return null;
    }
    public function setRememberToken($value){}
    public function getRememberTokenName(){
        return null;
    }

    public function logoutAllDevices(){
        $this->rememberTokens()->delete();
    }
    public function logoutDevice($token_id){
        $this->rememberTokens()->where('id',$token_id)->delete();
    }
    public function activeDevices(){
        return $this->rememberTokens()->where('expires_at', '>', Carbon::now())->get();
    }


    // all accounts
    public function accounts(){
        return $this->hasMany(Account::class);
    }

    // active account
    public function account(){
        return $this->hasOne(Account::class,'id','account_id');
    }

    public function storage(){
        $storage = new ModelStorage($this->getTable(), $this->id,0);
        return $storage;
    }

}