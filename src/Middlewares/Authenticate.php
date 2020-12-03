<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate {

    public function handle($request, Closure $next){
        if (!Auth::check()) {
            return redirect(config('rbac.unauthenticated_redirect_uri'))->with('message', 'You need to login first');
        }
        if (Auth::user()->otpEnabled() && !$request->session()->has(config('rbac.otp_session_identifier'))) {
            return redirect()->route('rbac.auth.otp')->with('message', 'You need to confirm OTP first');
        }
        return $next($request);
    }

}