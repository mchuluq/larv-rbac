<?php

namespace Mchuluq\Larv\Rbac\Controllers;

use Mchuluq\Larv\Rbac\Traits\Account;
use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller{

    use Account;

    function doLogin(Request $req){
        if ($this->hasTooManyLoginAttempts($req)) {
            if ($req->isJson() || $req->wantsJson()) {
                return response()->json([
                    'error' => [
                        'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
                        'code'        => 'TOO_MANY_REQUEST',
                        'message' => 'Too many login attempts. Please try again later.'
                    ],
                ], Response::HTTP_TOO_MANY_REQUESTS);
            } else {
                return abort(Response::HTTP_TOO_MANY_REQUESTS, 'Too many login attempts. Please try again later.');
            }
        } else {
            if ($req->isMethod('post')) {
                return $this->login($req);
            } else {
                $data['title'] = 'Login';
                return view(config('rbac.views.login'), $data);
            }
        }
    }

    function doLogout(Request $req){
        return $this->logout($req);
    }

    function doOtp(Request $req){
        if ($req->isMethod('post')) {
            return $this->attemptOtp($req);
        } else {
            $data['title'] = 'OTP';
            $data['url'] = route('rbac.auth.otp');
            return view(config('rbac.views.otp_confirm'), $data);
        }
    }

    function passwordForgot(Request $req){
        if ($req->isMethod('post')) {
            return $this->sendResetLinkEmail($req);
        } else {
            $data['title'] = 'Forgot password';
            return view(config('rbac.views.email'), $data);
        }
    }

    function passwordReset(Request $req, $token = null){
        if ($req->isMethod('post')) {
            return $this->reset($req);
        } else {
            $data['title'] = 'Forgot password';
            return view(config('rbac.views.reset'), $data)->with(
                ['token' => $token, 'email' => $req->email]
            );
        }
    }

    function passwordConfirm(Request $req){
        if ($req->isMethod('post')) {
            return $this->confirm($req);
        } else {
            return view(config('rbac.views.confirm'));
        }
    }

    function accountSwitch(Request $req,$account_id=null,$default=null){
        if(!$account_id){
            $data['user'] = Auth::user();
            $data['accounts'] = Auth::user()->accounts()
            // ->with('accountable')->whereHas('accountable')
            ->where('active', true)->get();
            Auth::user()->update(['account_id' => null]);
            return view(config('rbac.views.account'), $data);
        }else{
            $account = Auth::user()->accounts()
            // ->with('accountable')->whereHas('accountable')
            ->where(['id' => $account_id, 'active' => true])->first();
            if (!$account) {
                abort(404);
            }
            if($default == 'default'){
                Auth::user()->update(['account_id' => $account_id]);
            }
            $req->session()->put('rbac.account', $account->toArray());
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
