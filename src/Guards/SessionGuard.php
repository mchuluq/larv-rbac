<?php namespace Mchuluq\Larv\Rbac\Guards;

use Illuminate\Support\Str;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Auth\SessionGuard as BaseGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\DB;
use Mchuluq\Larv\Rbac\Helpers\DeviceHelper;
use Mchuluq\Larv\Rbac\Rbac;

class SessionGuard extends BaseGuard{
    
    protected $expire;
    protected $rbac;

    public function __construct($name,UserProvider $provider,Session $session,Request $request = null,$expire = 10080) {
        parent::__construct($name, $provider, $session, $request);
        $this->expire = $expire ?: 10080;
    }

    public function login(AuthenticatableContract $user, $remember = false){
        $this->updateSession($user->getAuthIdentifier());
        if ($user->account_id) {
            $this->rbac()->buildSession($user->account_id);
        }
        $remember_token = null;
        if ($remember) {
            $request = request();
            $fingerprint = DeviceHelper::getSimpleFingerprint($request);            
            $existingToken = $user->rememberTokens()->where('device_fingerprint', $fingerprint)->where('expires_at', '>', now())->first();
            if ($existingToken) {
                $token = $existingToken->token;
                $existingToken->update(['last_used_at' => now()]);
            } else {
                $token = $this->createRememberToken($user);
            }
            if($token){
                $remember_token = $token;
            }
            $this->queueRecallerCookie($user, $token);
        }
        $this->linkSessionToDeviceToken($remember_token);
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    protected function linkSessionToDeviceToken($remember_token){
        if (!$remember_token || config('session.driver') !== 'database') {
            return;
        }
        $session_id = $this->session->getId();
        DB::table(config('session.table', 'sessions'))->where('id', $session_id)->update(['remember_token' => $remember_token]);
    }

   /**
     * Log the user out of the application.
     * Hanya hapus remember token untuk device saat ini
     *
     * @return void
     */
    public function logout(){
        $user = $this->user();
        if ($user && $this->recaller()) {
            $recaller = $this->recaller();
            $this->provider->deleteRememberToken(
                $user->getAuthIdentifier(),
                $recaller->token()
            );
        }
        // Clear session
        $this->clearUserDataFromStorage();
        if (isset($this->events)) {
            $this->events->dispatch(new \Illuminate\Auth\Events\Logout($this->name, $user));
        }
        // Clear remember cookie
        $this->getCookieJar()->queue($this->getCookieJar()->forget($this->getRecallerName()));
        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Logout dari semua device
     *
     * @return void
     */
    public function logoutAllDevices(){
        $user = $this->user();
        if ($user) {
            // Hapus semua remember tokens
            $this->provider->purgeRememberTokens($user->getAuthIdentifier());
        }
        // Lakukan logout normal
        $this->logout();
    }

    /**
     * Logout dari device lain (kecuali device saat ini)
     *
     * @return void
     */
    public function logoutOtherDevices($password, $attribute = 'password'){
        // Early return if no user
        if (!$this->user()) {
            return null;
        }
        $user = $this->user();        
        // Verify password before logout
        if (!$this->provider->validateCredentials($user, [$attribute => $password])) {
            return false;
        }
        $currentSessionId = $this->session->getId();
        // STEP 1: Delete sessions from database (except current)
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getAuthIdentifier())->where('id', '!=', $currentSessionId)->delete();
        }
        // STEP 2:Rehash user password (Laravel's session invalidation for other devices)
        $result = $this->rehashUserPassword($password, $attribute);
        
        // STEP 3: Delete other device tokens
        if ($this->recaller()) {
            $currentToken = $this->recaller()->token();
            // Delete all tokens except current one
            $user->rememberTokens()->where('token', '!=', $currentToken)->delete();
        } else {
            $user->rememberTokens()->delete();
        }
        
        // STEP 4: Queue recaller cookie for current device if exists
        if ($this->recaller()) {
            $this->queueRecallerCookie($user, $this->recaller()->token());
        }
        // Fire other device logout event
        $this->fireOtherDeviceLogoutEvent($user);
        return $result;
    }

    /**
     * Override cycle remember token
     * Pastikan token di-cycle dengan benar
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function cycleRememberToken(AuthenticatableContract $user){
        // Generate token baru
        $token = Str::random(60);
        // Update melalui provider
        $this->provider->updateRememberToken($user, $token);
        // Set cookie baru
        $this->queueRecallerCookie($user,$token);
    }

    protected function queueRecallerCookie(AuthenticatableContract $user,$token=null){
        // Token must be provided or generated
        if (is_null($token)) {
            $token = Str::random(60);            
            // Also save to database if generating new token
            $this->provider->updateRememberToken($user, $token);
        }
        // Create cookie value: user_id|token|password_hash
        $value = $user->getAuthIdentifier() . '|' . $token . '|' . $user->getAuthPassword();
        $this->getCookieJar()->queue($this->createRecaller($value));
        $value = $user->getAuthIdentifier().'|'.$token.'|'.$user->getAuthPassword();
        $this->getCookieJar()->queue($this->createRecaller($value));
    }

    protected function createRememberToken(AuthenticatableContract $user){
        $token = $this->getNewToken();        
        // Add new remember token
        $this->provider->addRememberToken($user->getAuthIdentifier(),$token,$this->expire);
        // Purge expired tokens
        $this->provider->purgeRememberTokens($user->getAuthIdentifier(), true);
        return $token;
    }

    protected function getNewToken(){
        return Str::random(60);
    }

    public function rbac(){
        if(!$this->rbac){
            $this->rbac = new Rbac($this->session,$this->user(),$this->recaller());
        }
        return $this->rbac;
    }
}
