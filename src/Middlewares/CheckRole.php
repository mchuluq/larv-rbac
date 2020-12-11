<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Mchuluq\Larv\Rbac\Models\Group;
use Mchuluq\Larv\Rbac\Traits\HasParameters;

use Closure;

class CheckRole{
    
    use HasParameters;

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

    function setAbortResponse($request){
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status_code' => 401,
                    'code'        => 'INSUFFICIENT_ROLE',
                    'message' => 'You are not in authorized role to access this resource.'
                ],
            ], 401);
        } else {
            return abort(401, 'You are not in authorized role to access this resource.');
        }
    }
}
