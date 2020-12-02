<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Mchuluq\Larv\Rbac\Authenticator\GoogleAuthenticator;

use Closure;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

class ConfirmOtp{
    
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
        $ga = new GoogleAuthenticator();
        if ($user->otpEnabled() && $this->shouldConfirmPassword($request)) {
            if (!$ga->verifyCode($user->otp_secret, $request->input(config('rbac.otp_input_name')))) {
                if ($request->expectsJson()) {
                    return $this->responseFactory->json([
                        'message' => 'OTP confirmation required.',
                    ], 423);
                }
                return $this->responseFactory->redirectGuest(
                    $this->urlGenerator->route($redirectToRoute ?? 'rbac.otp.confirm')
                );
            }
        }
        return $next($request);
    }

    protected function shouldConfirmPassword($request){
        $confirmedAt = time() - $request->session()->get('rbac.otp_confirmed_at', 0);
        return $confirmedAt > $this->otpTimeout;
    }

}
