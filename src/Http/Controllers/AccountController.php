<?php

namespace Mchuluq\Larv\Rbac\Http\Controllers;

use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\ValidationException;

class AccountController extends Controller{

    protected function attemptOtp(Request $req){
        $req->validate([config('rbac.otp_input_name') => 'required']);
        $ga = new GoogleAuthenticator();
        if (!$ga->verifyCode($this->guard()->user()->otp_secret, $req->input(config('rbac.otp_input_name')))) {
            throw ValidationException::withMessages(['otp' => [__('rbac::rbac.otp_failed_response')],]);
        }
        $req->session()->put(config('rbac.otp_session_identifier'), time());
        return $req->wantsJson() ? response()->json(['message'=>__('rbac::rbac.otp_success_response')]) : redirect()->intended($this->redirectPath());
    }

    protected function guard(){
        return Auth::guard();
    }

    // REDIRECT USER
    public function redirectPath(){
        $account_id = Auth::user()->account_id;
        return route('rbac.account.switch',['account_id'=>$account_id]) ?? '/home';
    }

    function doOtp(Request $req){
        if ($req->isMethod('post')) {
            return $this->attemptOtp($req);
        } else {
            $data['url'] = route('auth.otp');
            $data['label'] = config('app.name')." (".$this->guard()->user()->email.")";
            return $req->wantsJson() ? response()->json($data) : view(config('rbac.views.otp_confirm'), $data);
        }
    }

    function accountSwitch(Request $req,$account_id=null){
        if(!$account_id){
            $data['user'] = Auth::user();
            $data['accounts'] = Auth::user()->accounts()
            // ->with('accountable')
            ->where('active', true)->get();
            return $req->wantsJson() ? response()->json($data) : view(config('rbac.views.account'), $data);
        }else{
            $build = Auth::buildSession($account_id);
            if(!$build){
                $msg = __('rbac::rbac.account_not_found');
                return $req->wantsJson() ? response()->json(['message'=>$msg]) : response()->view('errors.404',['message'=>$msg],404);
            }
            $msg = __('rbac::rbac.account_switched');
            return $req->wantsJson() ? response()->json(['message'=>$msg]) : redirect()->intended(config('rbac.authenticated_redirect_uri'));
        }
    }

    
    function otpRequest(Request $req){
        $ga = new GoogleAuthenticator();
        $user = $this->guard()->user();
        $data["user"] = $user;
        $data["otp_secret"] = $ga->createSecret();
        $data["otp_qr_image"] = $ga->getQRCodeGoogleUrl($user->email,$data["otp_secret"],config('app.name'));            
        return $req->wantsJson() ? response()->json($data) : view(config('rbac.views.otp_register'), $data);
    }
    function otpRegister(Request $req){
        $user = $this->guard()->user();
        $req->validate(['otp_secret' => 'required']);
        $user->otp_secret = $req->input('otp_secret');
        $user->save();
        $msg = __('rbac::rbac.otp_enabled_success');
        return $req->wantsJson() ? response()->json(['message'=>$msg]) : redirect(config('rbac.authenticated_redirect_uri'))->with('message', $msg);
    }
    function otpUnregister(Request $req){
        $user = $this->guard()->user();
        $req->validate(['password','password:rbac-web-guard']);
        $user->otp_secret = null;
        $user->save();
        $msg = __('rbac::rbac.otp_disabled_success');
        return $req->wantsJson() ? response()->json(['message'=>$msg]) : redirect(config('rbac.authenticated_redirect_uri'))->with('message', $msg);
    }
}
