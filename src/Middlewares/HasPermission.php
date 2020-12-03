<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Illuminate\Support\Facades\Auth;
use Closure;

class HasPermission{

    public function handle($request, Closure $next){
        if (!Auth::check()) {
            return redirect(config('rbac.unauthenticated_redirect_uri'))->with('message', 'You need to login first');
        }
        if (Auth::user()->otpEnabled() && !$request->session()->has('rbac.auth_otp_confirmed_at')) {
            return redirect()->route('rbac.auth.otp')->with('message', 'You need to login first');
        }

        // $user = Auth::user();
        // $route_name = $request->route()->getAction('as');
        // $route_uac = $request->route()->getAction('uac');
        // if (!$route_name) {
        //     return $this->setAbortResponse($request);
        // } elseif ($user->isHasPermission($route_name)) {
        //     return $next($request);
        // } else if ($user->isHasAlternativePermission($route_name, $route_uac)) {
        //     return $next($request);
        // } else {
        //     return $this->setAbortResponse($request);
        // }
    }

    function setAbortResponse($request){
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status_code' => 401,
                    'code'        => 'INSUFFICIENT_PERMISSIONS',
                    'message' => 'You are not authorized to access this resource.'
                ],
            ], 401);
        } else {
            return abort(401, 'You are not authorized to access this resource.');
        }
    }
}
