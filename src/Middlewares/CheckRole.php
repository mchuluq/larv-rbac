<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Mchuluq\Larv\Rbac\Models\Group;

use Closure;

class CheckRole extends Authenticate{

    public function handle($request, Closure $next,$role=null){
        $roles = $request->session()->get('rbac.user.roles',[]);
        if (!$roles) {
            return $this->setAbortResponse($request);
        } elseif (in_array($roles,$role)) {
            return $next($request);
        } else {
            $group_id = $request->session()->get('rbac.account.group_id', false);
            $group = Group::find($group_id)->getRoles();
            if(!$group || !isset($group['roles'])){
                return $this->setAbortResponse($request);
            }elseif(in_array($group['roles'], $role)) {
                return $next($request);
            }            
        }
        return $this->setAbortResponse($request);
    }
}
