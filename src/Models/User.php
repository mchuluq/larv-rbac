<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

// use Mchuluq\Larv\Rbac\Models\RememberToken as RememberToken
// use Mchuluq\Larv\Rbac\Models\Account as Account
class User extends Authenticatable{

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'avatar_url','email_verified_at','password','active','account_id','otp_secret'
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
        $this->attributes['password'] = Hash::make($string);
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
        if(!is_null($value)){
            $this->attributes['otp_secret'] = encrypt($value);
        }else{
            $this->attributes['otp_secret'] = $value;
        }
    }

    public function getOtpSecretAttribute($value){
        if(!is_null($value)){
            return decrypt($value);
        }else{
            return $value;
        }
    }

    public function otpEnabled(){
        return (!is_null($this->otp_secret)) ? true : false;
    }
}
