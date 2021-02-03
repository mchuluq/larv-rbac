<?php

namespace Mchuluq\Larv\Rbac\Guards;

use Illuminate\Support\Str;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Auth\SessionGuard as BaseGuard;
use Illuminate\Auth\Events\Logout as LogoutEvent;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

use Mchuluq\Larv\Rbac\Rbac;

class SessionGuard extends BaseGuard{
    
    protected $expire;

    protected $rbac;

    public function __construct($name,UserProvider $provider,Session $session,Request $request = null,$expire = 10080) {
        parent::__construct($name, $provider, $session, $request);
        $this->expire = $expire ?: 10080;
    }

    public function user(){
        if ($this->loggedOut) {
            return;
        }
        if (!is_null($this->user)) {
            return $this->user;
        }
        $id = $this->session->get($this->getName());
        if (!is_null($id)) {
            if ($this->user = $this->provider->retrieveById($id)) {
                $this->fireAuthenticatedEvent($this->user);
            }
        }
        $recaller = $this->recaller();
        if (is_null($this->user) && !is_null($recaller)) {
            $this->user = $this->userFromRecaller($recaller);
            if ($this->user) {
                $this->replaceRememberToken($this->user, $recaller->token());
                $this->updateSession($this->user->getAuthIdentifier());
                $this->rbac()->authenticateOtp(true);
                $this->rbac()->buildSession($this->user->account_id);
                $this->fireLoginEvent($this->user, true);
            }
        }
        return $this->user;
    }

    protected function replaceRememberToken(AuthenticatableContract $user, $token){
        $this->provider->replaceRememberToken($user->getAuthIdentifier(),$token,$newToken = $this->getNewToken(),$this->expire);
        $this->queueRecallerCookie($user, $newToken);
    }

    public function login(AuthenticatableContract $user, $remember = false){
        $this->updateSession($user->getAuthIdentifier());
        $this->rbac()->buildSession($user->account_id);
        if ($remember) {
            $token = $this->createRememberToken($user);
            $this->queueRecallerCookie($user, $token);
        }
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    protected function createRememberToken(AuthenticatableContract $user){
        $this->provider->addRememberToken($user->getAuthIdentifier(), $token = $this->getNewToken(), $this->expire);
        $this->provider->purgeRememberTokens($user->getAuthIdentifier(), true);
        return $token;
    }

    protected function getNewToken(){
        return Str::random(60);
    }

    public function logout(){
        $user = $this->user();
        $this->clearUserDataFromStorage();
        if (isset($this->events)) {
            $this->events->dispatch(new LogoutEvent($this->name, $user));
        }
        $this->user = null;
        $this->loggedOut = true;
    }

    protected function clearUserDataFromStorage(){
        $this->session->remove($this->getName());
        $recaller = $this->recaller();
        if (!is_null($recaller)) {
            $this->getCookieJar()->queue($this->getCookieJar()->forget($this->getRecallerName()));
            $this->provider->deleteRememberToken($recaller->id(), $recaller->token());
        }
    }

    public function logoutOtherDevices($password, $attribute = 'password'){
        if (!$this->user()) {
            return;
        }
        $this->provider->purgeRememberTokens($this->user()->getAuthIdentifier());
        return parent::logoutOtherDevices($password, $attribute);
    }

    protected function queueRecallerCookie(AuthenticatableContract $user, $token = null){
        if (is_null($token)) {
            $token = $this->createRememberToken($user);
        }
        $this->getCookieJar()->queue($this->createRecaller($user->getAuthIdentifier() . '|' . $token . '|' . $user->getAuthPassword()));
    }

    protected function createRecaller($value){
        return $this->getCookieJar()->make($this->getRecallerName(), $value, $this->expire);
    }

    public function rbac(){
        $this->rbac = new Rbac($this->session,$this->user(),$this->recaller());
        return $this->rbac;
    }

    // get current remember token if any
    public function getRememberToken(){
        $recaller = $this->recaller();
        return ($recaller) ? $recaller->token() : null;
    }
}
