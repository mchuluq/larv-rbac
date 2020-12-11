<?php

namespace Mchuluq\Larv\Rbac;

use Carbon\Carbon;
use Illuminate\Auth\EloquentUserProvider as BaseUserProvider;
use Illuminate\Support\Str;
use Request;

class UserProvider extends BaseUserProvider{
    
    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
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

    /**
     * Add a token value for the "remember me" session.
     *
     * @param  string  $value
     * @param  int $expire
     * @return void
     */
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

    /**
     * Replace "remember me" token with new token.
     *
     * @param  string $token
     * @param  string $newToken
     * @param  int $expire
     *
     * @return void
     */
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

    /**
     * Delete the specified "remember me" token for the given user.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return null
     */
    public function deleteRememberToken($identifier, $token){
        $model = $this->getModelById($identifier);
        if ($model && $token = $model->rememberTokens()->where('token', $token)->first()) {
            $token->delete();
        }
    }

    /**
     * Purge old or expired "remember me" tokens.
     *
     * @param  mixed $identifier
     * @param  bool $expired
     * @return null
     */
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

    /**
     * Gets the user based on their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
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

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
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
