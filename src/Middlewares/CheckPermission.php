<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Closure;

class CheckPermission extends Authenticate{

    public function handle($request, Closure $next, $route=null){
        $route = $route ?? $request->route()->getAction('as');
        $permissions = $request->session()->get('rbac.permissions',[]);
        if (!$route || !$permissions) {
            return $this->setAbortResponse($request);
        } elseif (in_array($permissions,$route)) {
            return $next($request);
        } else {
            return $this->setAbortResponse($request);
        }
    }
}
