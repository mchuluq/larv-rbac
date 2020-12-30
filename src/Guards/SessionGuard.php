<?php

namespace Mchuluq\Larv\Rbac\Guards;

use Mchuluq\Larv\Rbac\Models\Permission;
use Mchuluq\Larv\Rbac\Models\DataAccess;
use Mchuluq\Larv\Rbac\Models\RoleActor;
use Mchuluq\Larv\Rbac\Models\Menu;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Auth\SessionGuard as BaseGuard;
use Illuminate\Auth\Events\Logout as LogoutEvent;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class SessionGuard extends BaseGuard{
    
    /**
     * Indicates the number of seconds a "remember me" token should be valid for.
     *
     * @var int
     */
    protected $expire;

    public function __construct($name,UserProvider $provider,Session $session,Request $request = null,$expire = 10080) {
        parent::__construct($name, $provider, $session, $request);
        $this->expire = $expire ?: 10080;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(){
        if ($this->loggedOut) {
            return;
        }

        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (!is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
        if (!is_null($id)) {
            if ($this->user = $this->provider->retrieveById($id)) {
                $this->fireAuthenticatedEvent($this->user);
            }
        }

        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
        $recaller = $this->recaller();
        if (is_null($this->user) && !is_null($recaller)) {
            $this->user = $this->userFromRecaller($recaller);
            if ($this->user) {
                $this->replaceRememberToken($this->user, $recaller->token());
                $this->updateSession($this->user->getAuthIdentifier());
                $this->buildSession($this->user->account_id);
                $this->fireLoginEvent($this->user, true);
            }
        }
        return $this->user;
    }

    protected function replaceRememberToken(AuthenticatableContract $user, $token){
        $this->provider->replaceRememberToken($user->getAuthIdentifier(),$token,$newToken = $this->getNewToken(),$this->expire);
        $this->queueRecallerCookie($user, $newToken);
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = false){
        $this->updateSession($user->getAuthIdentifier());
        $this->buildSession($user->account_id);
        
        // If the user should be permanently "remembered" by the application we will
        // queue a permanent cookie that contains the encrypted copy of the user
        // identifier. We will then decrypt this later to retrieve the users.
        if ($remember) {
            $token = $this->createRememberToken($user);
            $this->queueRecallerCookie($user, $token);
        }
        
        // If we have an event dispatcher instance set we will fire an event so that
        // any listeners will hook into the authentication events and run actions
        // based on the login and logout events fired from the guard instances.
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    /**
     * Create a new "remember me" token for the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function createRememberToken(AuthenticatableContract $user){
        $this->provider->addRememberToken($user->getAuthIdentifier(), $token = $this->getNewToken(), $this->expire);
        $this->provider->purgeRememberTokens($user->getAuthIdentifier(), true);
        return $token;
    }

    /**
     * Creates a new token for "remember me" sessions.
     *
     * @return string
     */
    protected function getNewToken(){
        return Str::random(60);
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(){
        $user = $this->user();

        // If we have an event dispatcher instance, we can fire off the logout event
        // so any further processing can be done. This allows the developer to be
        // listening for anytime a user signs out of this application manually.
        $this->clearUserDataFromStorage();

        if (isset($this->events)) {
            $this->events->dispatch(new LogoutEvent($this->name, $user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * @return void
     */
    protected function clearUserDataFromStorage(){
        $this->session->remove($this->getName());
        $recaller = $this->recaller();
        if (!is_null($recaller)) {
            $this->getCookieJar()->queue($this->getCookieJar()->forget($this->getRecallerName()));
            $this->provider->deleteRememberToken($recaller->id(), $recaller->token());
        }
    }

    /**
     * Invalidate other sessions for the current user.
     *
     * The application must be using the AuthenticateSession middleware.
     *
     * @param  string  $password
     * @param  string  $attribute
     * @return bool|null
     */
    public function logoutOtherDevices($password, $attribute = 'password'){
        if (!$this->user()) {
            return;
        }
        $this->provider->purgeRememberTokens($this->user()->getAuthIdentifier());
        return parent::logoutOtherDevices($password, $attribute);
    }

    /**
     * Queue the recaller cookie into the cookie jar.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function queueRecallerCookie(AuthenticatableContract $user, $token = null){
        if (is_null($token)) {
            $token = $this->createRememberToken($user);
        }

        $this->getCookieJar()->queue($this->createRecaller($user->getAuthIdentifier() . '|' . $token . '|' . $user->getAuthPassword()));
    }

    /**
     * Create a "remember me" cookie for a given ID.
     *
     * @param  string  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function createRecaller($value){
        return $this->getCookieJar()->make($this->getRecallerName(), $value, $this->expire);
    }

    public function buildSession($account_id){
        $user = $this->user();
        $account = $user->accounts()
        // ->with('accountable')->whereHas('accountable')
        ->where(['id' => $account_id, 'active' => true])->first();
        if (!$account) {
            return false;
        }
        $user->update(['account_id' => $account_id]);
        $account->getRoles()->getPermissions();
        $recaller = $this->recaller();
        $data = array(
            'via_remember' => ($recaller) ? true : false,
            'account' => $account->toArray(),
            'user' => $user->toArray(),
            'permissions' => $this->getPermissions($account->id, $account->group_id),
            'data_access' => $this->getDataAccess($account->id, $account->group_id),
        );
        $data['menu'] = $this->buildMenuTree($data['permissions']);
        session()->put('rbac', $data);
        return true;
    }

    function getPermissions($account_id, $group_id){
        $tperm = with(new Permission)->getTable();
        $troleact = with(new RoleActor)->getTable();

        $result = [];
        $res = DB::table($tperm . " AS a")->select("a.route AS route")
        ->where("a.account_id", $account_id)
            ->orWhere("a.group_id", $group_id)
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.account_id = ?)))", [$account_id])
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.group_id = ?)))", [$group_id])
            ->groupBy('a.route')->get();
        foreach ($res as $r) {
            $result[] = $r->route;
        }
        return $result;
    }
    
    function getDataAccess($account_id, $group_id){
        $tda = with(new DataAccess)->getTable();
        $troleact = with(new RoleActor)->getTable();

        $result = [];
        $res = DB::table($tda . " AS a")->select("a.data_type","a.data_id")
        ->where("a.account_id", $account_id)
            ->orWhere("a.group_id", $group_id)
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.account_id = ?)))", [$account_id])
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.group_id = ?)))", [$group_id])
            ->groupBy('a.data_id','a.data_type')->get();
        foreach ($res as $r) {
            $result[$r->data_type] = $r->data_id;
        }
        return $result;
    }

    protected function buildMenuTree($access_list){
        $get = Menu::whereIn('route',$access_list)->orWhereNull('route')->whereHas('children')->orderBy('display_order','asc')->get()->toTree()->toArray();
        $menu = [];
        foreach($get as $row){
            $menu[$row['position']][] = $row;
        }
        return $menu;
    }

    function hasPermission($route){
        $permissions = session()->get('rbac.permissions',[]);
        return in_array($permissions,$route);
    }

    function getMenu($position=null){
        $menus = session()->get('rbac.menu',[]);
        if(!$position){
            return $menus;
        }elseif(isset($menus[$position])){
            return $menus[$position];
        }else{
            return [];
        }
    }
}
