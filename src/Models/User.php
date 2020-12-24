<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

// use Mchuluq\Larv\Rbac\Models\RememberToken as RememberToken
// use Mchuluq\Larv\Rbac\Models\Account as Account
class User extends Authenticatable{

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'avatar_url','email_verified_at','password','active','account_id','otp_secret','secret','last_login_ip','last_login_at'
    ];
    
    use BaseAuthenticatable;

    protected static function boot(){
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function rememberTokens(){
        return $this->hasMany(RememberToken::class);
    }
    
    public function accounts(){
        return $this->hasMany(Account::class);
    }

    public function setPasswordAttribute($string=null){
        if(!empty($string)){
            $this->attributes['password'] = Hash::make($string);
        }else{
            unset($this->attributes['password']);
        }
    }

    public function setAvatarUrlAttribute($value){
        if(filter_var($value,FILTER_VALIDATE_EMAIL)){
            $url = 'https://www.gravatar.com/avatar/';
            $url .= md5(strtolower(trim($value)));
            $url .= '?'.http_build_query(config('uac.gravatar_options'));
            $this->attributes['avatar_url'] = $url;
        }else{
            $this->attributes['avatar_url'] = $value;
        }
    }

    public function setOtpSecretAttribute($value){
        $this->attributes['otp_secret'] = (!is_null($value)) ? encrypt($value) : $value;
    }

    public function getOtpSecretAttribute($value){
        return (!is_null($value)) ? decrypt($value) : $value;
    }

    public function otpEnabled(){
        return (!is_null($this->otp_secret)) ? true : false;
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
}
