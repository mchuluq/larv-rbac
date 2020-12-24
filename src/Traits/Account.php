<?php

namespace Mchuluq\Larv\Rbac\Traits;

use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait Account{

    protected function attemptOtp(Request $request){
        $request->validate([config('rbac.otp_input_name') => 'required']);
        $ga = new GoogleAuthenticator();
        if (!$ga->verifyCode($this->guard()->user()->otp_secret, $request->input(config('rbac.otp_input_name')))) {
            return $this->sendFailedOtpResponse($request);
        }
        $request->session()->put(config('rbac.otp_session_identifier'), time());
        return $request->wantsJson() ? new Response('', 204) : redirect()->intended($this->redirectPath());
    }
    
    protected function sendFailedOtpResponse(Request $request){
        throw ValidationException::withMessages(['otp' => [config('rbac.otp_failed_response')],]);
    }

    protected function guard(){
        return Auth::guard();
    }

    // REDIRECT USER
    public function redirectPath(){
        $account_id = Auth::user()->account_id;
        return route('rbac.account.switch',['account_id'=>$account_id]) ?? '/home';
    }
}
