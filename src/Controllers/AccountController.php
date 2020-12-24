<?php

namespace Mchuluq\Larv\Rbac\Controllers;

use Mchuluq\Larv\Rbac\Traits\Account;
use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller{

    use Account;

    function doOtp(Request $req){
        if ($req->isMethod('post')) {
            return $this->attemptOtp($req);
        } else {
            $data['title'] = 'Konfirmasi OTP';
            $data['url'] = route('auth.otp');
            $data['email'] = $this->guard()->user()->email;
            $data['name'] = config('app.name');
            return view(config('rbac.views.otp_confirm'), $data);
        }
    }

    function accountSwitch(Request $req,$account_id=null){
        if(!$account_id){
            $data['user'] = Auth::user();
            $data['accounts'] = Auth::user()->accounts()
            // ->with('accountable')->whereHas('accountable')
            ->where('active', true)->get();
            return view(config('rbac.views.account'), $data);
        }else{
            $build = Auth::buildSession($account_id);
            if(!$build){
                abort(404);
            }
            return $req->wantsJson() ? new Response('', 204) : redirect()->intended(config('rbac.authenticated_redirect_uri'));
        }
    }

    function otpRegister(Request $req){
        $user = $this->guard()->user();
        if($user->otpEnabled()){
            // prosedur matikan OTP
            if($req->isMethod('post')){
                $req->validate(['password','password:rbac-web']);
                $user->otp_secret = null;
                $user->save();
                return redirect(config('rbac.authenticated_redirect_uri'))->with('rbac_status', config('rbac.otp_disabled_success'));
            }else{
                $data["user"] = $user;
                return view(config('rbac.views.otp_register'), $data);
            }
        }else{
            //  prosedur nyalakan OTP
            if ($req->isMethod('post')) {
                $req->validate(['otp_secret' => 'required']);
                $user->otp_secret = $req->input('otp_secret');
                $user->save();
                return redirect(config('rbac.authenticated_redirect_uri'))->with('rbac_status', config('rbac.otp_enabled_success'));
            } else {
                $ga = new GoogleAuthenticator();
                $data["user"] = $user;
                $data["otp_secret"] = $ga->createSecret();
                $data["otp_qr_image"] = $ga->getQRCodeGoogleUrl(
                    $user->email,
                    $data["otp_secret"],
                    config('app.name')
                );
                return view(config('rbac.views.otp_register'), $data);
            }
        }
    }
}
