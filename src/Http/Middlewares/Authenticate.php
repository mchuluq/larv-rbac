<?php

namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Mchuluq\Larv\Rbac\Traits\HasParameters;

class Authenticate {

    use HasParameters;

    public function handle($request, Closure $next, $checkAccount=true){
        if (!Auth::check()) {
            return $this->setAbortResponse($request);
        }elseif (Auth::user()->otpEnabled() && !$request->session()->has(config('rbac.otp_session_identifier'))) {
            return $this->sendNeedOtpConfirm($request);
        } elseif (!$request->session()->has('rbac.account') && $checkAccount == true) {
            return $this->sendNeedSelectAccount($request);
        }
        return $next($request);
    }

    function setAbortResponse($request){
        $msg = 'You are not authorized to access this resource';
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'code' => 'AUTH_REQUIRED',
                'message' => $msg,
                'redirect_url' => url(config('rbac.unauthenticated_redirect_uri'))
            ], 401);
        } else {
            return redirect(config('rbac.unauthenticated_redirect_uri'))->with('message', 'You need to login first');
        }
    }

    protected function sendNeedOtpConfirm($request){
        $msg = 'You need to confirm OTP';
        if($request->isJson() || $request->wantsJson()){
            return response()->json([
                'code' => 'OTP_REQUIRED',
                'message' => $msg,
                'redirect_url' => route('auth.otp')
            ],403);
        }else{
            return redirect()->route('auth.otp')->with('message', $msg);
        }
    }

    protected function sendNeedSelectAccount($request){
        $msg = 'You need to select an Account';
        if($request->isJson() || $request->wantsJson()){
            return response()->json([
                'code' => 'ACCOUNT_SELECT_REQUIRED',
                'message' => $msg,
                'redirect_url' => route('rbac.account.switch')
            ],403);
        }else{
            return redirect()->route('rbac.account.switch')->with('message', $msg);
        }
    }
}