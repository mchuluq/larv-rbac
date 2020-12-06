<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Closure;

class CheckGroup extends Authenticate{

    public function handle($request, Closure $next, $group=null){
        $group_id = $request->session()->get('rbac.account.group_id');
        if ($group_id != $group) {
            return $this->setAbortResponse($request);
        }
        return $next($request);
    }
}
