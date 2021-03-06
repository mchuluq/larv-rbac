<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable{

    use BaseAuthenticatable;

    public function rememberTokens(){
        return $this->hasMany(RememberToken::class);
    }    
    public function accounts(){
        return $this->hasMany(Account::class);
    }
    public function account(){
        return $this->hasOne(Account::class,'id','account_id');
    }

    public function setOtpSecretAttribute($value){
        $this->attributes['otp_secret'] = (!is_null($value)) ? encrypt($value) : $value;
    }
    public function getOtpSecretAttribute($value){
        return (!is_null($value)) ? decrypt($value) : $value;
    }

    public function otpEnabled(){
        return (!is_null($this->otp_secret));
    }

    public function setSettingsAttribute($value){
        $this->attributes['settings'] = (!is_null($value)) ? encrypt(json_encode($value)) : $value;
    }
    public function getSettingsAttribute($value){
        return (!is_null($value)) ? json_decode(decrypt($value),true) : $value;
    }

    public function setUserSetting($key,$value){
        $settings = $this->settings;
        Arr::set($settings,$key,$value);
        $this->settings = $settings;
        return $this;
    }
    
    public function getUserSetting($key,$default=null){
        $settings = $this->settings;
        return Arr::get($settings,$key,$default);
    }

    public function setPasswordAttribute($string=null){
        if(!empty($string)){
            $info = password_get_info($string);
            if($info['algo'] == 0){
                $this->attributes['password'] = Hash::make($string);
            }else{
                $this->attributes['password'] = $string;
            }
        }else{
            unset($this->attributes['password']);
        }
    }

    public function credentials(){
        return $this->hasMany(Credential::class);
    }
}
