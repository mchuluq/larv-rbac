<?php

namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Mchuluq\Larv\Rbac\Traits\HasParameters;

use Closure;

class CheckGroup{

    use HasParameters;

    public function handle($request, Closure $next, $group=null){
        $group_id = $request->session()->get('rbac.account.group_id');
        if ($group_id != $group) {
            return $this->setAbortResponse($request);
        }
        return $next($request);
    }

    function setAbortResponse($request){
        $msg = 'You are not in authorized group to access this resource';
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'code'  => 'INSUFFICIENT_GROUP',
                'message' => $msg
            ], 401);
        } else {
            return abort(401, $msg);
        }
    }
}
