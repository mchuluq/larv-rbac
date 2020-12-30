<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Mchuluq\Larv\Rbac\Traits\HasParameters;

use Closure;

class CheckPermission{

    use HasParameters;

    public function handle($request, Closure $next, $route=null){
        $route = $route ?? $request->route()->getAction('as');
        $permissions = $request->session()->get('rbac.permissions',[]);
        if (!$route || !$permissions) {
            return $this->setAbortResponse($request);
        } elseif (in_array($route,$permissions)) {
            return $next($request);
        } else {
            return $this->setAbortResponse($request);
        }
    }

    function setAbortResponse($request){
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status_code' => 401,
                    'code'        => 'INSUFFICIENT_GROUP',
                    'message' => 'You are not in authorized group to access this resource.'
                ],
            ], 401);
        } else {
            return abort(401, 'You are not in authorized group to access this resource.');
        }
    }
}
