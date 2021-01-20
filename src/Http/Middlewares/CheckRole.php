<?php

namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Mchuluq\Larv\Rbac\Models\Group;
use Mchuluq\Larv\Rbac\Traits\HasParameters;

use Closure;

class CheckRole{
    
    use HasParameters;

    public function handle($request, Closure $next,$role=null){
        $roles = $request->session()->get('rbac.user.roles',[]);
        if (!$roles) {
            return $this->setAbortResponse($request);
        } elseif (in_array($role,$roles)) {
            return $next($request);
        } else {
            $group_id = $request->session()->get('rbac.account.group_id', false);
            $group = Group::find($group_id)->getRoles();
            if(!$group || !isset($group['roles'])){
                return $this->setAbortResponse($request);
            }elseif(in_array($role,$group['roles'])) {
                return $next($request);
            }            
        }
        return $this->setAbortResponse($request);
    }

    function setAbortResponse($request){
        $msg = 'You are not in authorized role to access this resource';
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'code' => 'INSUFFICIENT_ROLE',
                'message' => $msg
            ], 403);
        } else {
            return abort(403, $msg);
        }
    }
}
