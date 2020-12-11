<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Closure;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Response;

use Mchuluq\Larv\Rbac\Traits\HasParameters;

class ConfirmOtp{

    use HasParameters;

    protected $responseFactory;
    protected $urlGenerator;
    protected $otpTimeout;

    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator){
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
        $this->otpTimeout = config('rbac.otp_timeout') ?: 10800;
    }

    public function handle($request, Closure $next, $redirectToRoute = null){
        $user = Auth::user();
        if ($user->otpEnabled() && $this->shouldConfirmOtp($request)) {
            $ga = new GoogleAuthenticator();
            if ($ga->verifyCode($user->otp_secret, $request->input(config('rbac.otp_input_name')))) {
                $request->session()->put(config('rbac.otp_confirm_identifier'), time());
                return $next($request);
            }
            return $this->makeRequestOTPResponse($request);
        }
        return $next($request);        
    }

    protected function shouldConfirmOtp($request){
        $confirmedAt = time() - $request->session()->get(config('rbac.otp_confirm_identifier'), 0);
        return $confirmedAt > $this->otpTimeout;
    }

    public function makeRequestOTPResponse($request){
        $data['title'] = 'Confirm OTP';
        $data['url'] = route('rbac.otp.confirm');
        $data['email'] = Auth::user()->email;
        $data['name'] = config('app.name');
        return new Response(view(config('rbac.views.otp_confirm'), $data),200);
    }

}
