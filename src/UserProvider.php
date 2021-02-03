<?php

namespace Mchuluq\Larv\Rbac;

use Carbon\Carbon;
use Illuminate\Auth\EloquentUserProvider as BaseUserProvider;
use Illuminate\Support\Str;
use Request;

class UserProvider extends BaseUserProvider{
    
    public function retrieveByToken($identifier, $token){
        if (!$model = $this->getModelById($identifier)) {
            return null;
        }
        $rememberTokens = $model->rememberTokens()->where('expires_at', '>', Carbon::now())->get();
        foreach ($rememberTokens as $rememberToken) {
            if (hash_equals($rememberToken->token, $token)) {
                return $model;
            }
        }
    }

    public function addRememberToken($identifier, $value, $expire){
        $model = $this->getModelById($identifier);
        if ($model) {
            $model->rememberTokens()->create([
                'token' => $value,
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('user-agent'),
                'expires_at' => Carbon::now()->addMinutes($expire),
            ]);
        }
    }

    public function replaceRememberToken($identifier, $token, $newToken, $expire){
        $model = $this->getModelById($identifier);
        if ($model) {
            $model->rememberTokens()->where('token', $token)->update([
                'token' => $newToken,
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('user-agent'),
                'expires_at' => Carbon::now()->addMinutes($expire),
            ]);
        }
    }

    public function deleteRememberToken($identifier, $token){
        $model = $this->getModelById($identifier);
        if ($model && $token = $model->rememberTokens()->where('token', $token)->first()) {
            $token->delete();
        }
    }

    public function purgeRememberTokens($identifier, $expired = false){
        $model = $this->getModelById($identifier);
        if ($model) {
            $query = $model->rememberTokens();
            if ($expired) {
                $query->where('expires_at', '<', Carbon::now());
            }
            $query->delete();
        }
    }

    protected function getModelById($identifier){
        $model = $this->createModel();
        return $model->where([$model->getAuthIdentifierName()=>$identifier,'active'=>true])->first();
    }

    public function retrieveByCredentials(array $credentials){
        if (
            empty($credentials) ||
            (count($credentials) === 1 &&
                Str::contains($this->firstCredentialKey($credentials), 'password'))
        ) {
            return;
        }
        $query = $this->newModelQuery();
        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }
        return $query->where('active',true)->first();
    }
}
