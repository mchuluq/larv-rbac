<?php

namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Mchuluq\Larv\Rbac\Traits\HasParameters;

use Closure;

class CheckPermission{

    use HasParameters;

    public function handle($request, Closure $next, $route=null){
        $route = $route ?? $request->route()->getAction('as');
        $permissions = $request->user()->storage()->get('rbac.permissions',[]);
        if (!$route || !$permissions) {
            return $this->setAbortResponse($request);
        } elseif (in_array($route,$permissions)) {
            return $next($request);
        } else {
            return $this->setAbortResponse($request);
        }
    }

    function setAbortResponse($request){
        $msg = 'You do not have sufficient access permissions';
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'code' => 'INSUFFICIENT_PERMISSION',
                'message' => $msg
            ], 403);
        } else {
            return abort(403, $msg);
        }
    }
}
