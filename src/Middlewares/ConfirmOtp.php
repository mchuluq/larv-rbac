<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Illuminate\Support\Facades\Auth;
use Closure;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

use Google\Authenticator\GoogleAuthenticator;

class ConfirmOtp{
    
    protected $responseFactory;
    protected $urlGenerator;

    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator){
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
    }

    public function handle($request, Closure $next, $redirectToRoute = null){
        $user = Auth::user();
        $ga = new GoogleAuthenticator();
        if ($user->otpEnabled()) {
            if (!$ga->checkCode($user->otp_secret, $request->input('rbac.otp_input_name'))) {
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

}
