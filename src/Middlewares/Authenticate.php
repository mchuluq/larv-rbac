<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

use Mchuluq\Larv\Rbac\Traits\HasParameters;

class Authenticate {

    use HasParameters;

    public function handle($request, Closure $next, $checkAccount=true){
        if (!Auth::check()) {
            return redirect(config('rbac.unauthenticated_redirect_uri'))->with('message', 'You need to login first');
        }elseif (Auth::user()->otpEnabled() && !$request->session()->has(config('rbac.otp_session_identifier'))) {
            return redirect()->route('auth.otp')->with('message', 'You need to confirm OTP first');
        } elseif (!$request->session()->has('rbac.account') && $checkAccount == true) {
            return redirect()->route('rbac.account.switch')->with('message', 'You need to select an Account');
        }
        return $next($request);
    }

    function setAbortResponse($request){
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status_code' => 401,
                    'code'        => 'AUTH_REQUIRED',
                    'message' => 'You are not authorized to access this resource.'
                ],
            ], 401);
        } else {
            return abort(401, 'You are not authorized to access this resource.');
        }
    }
}