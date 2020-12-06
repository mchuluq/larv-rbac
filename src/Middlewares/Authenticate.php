<?php

namespace Mchuluq\Larv\Rbac\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;

class Authenticate {

    protected $responseFactory;
    protected $urlGenerator;
    protected $otpTimeout;

    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator){
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
        $this->otpTimeout = config('rbac.otp_timeout') ?: 10800;
    }

    public function handle($request, Closure $next, $param=null){
        if (!Auth::check()) {
            return redirect(config('rbac.unauthenticated_redirect_uri'))->with('message', 'You need to login first');
        }elseif (Auth::user()->otpEnabled() && !$request->session()->has(config('rbac.otp_session_identifier'))) {
            return redirect()->route('rbac.auth.otp')->with('message', 'You need to confirm OTP first');
        }
        return $next($request);
    }

    function setAbortResponse($request){
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status_code' => 401,
                    'code'        => 'INSUFFICIENT_ROLES',
                    'message' => 'You are not authorized to access this resource.'
                ],
            ], 401);
        } else {
            return abort(401, 'You are not authorized to access this resource.');
        }
    }

}