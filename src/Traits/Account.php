<?php

namespace Mchuluq\Larv\Rbac\Traits;

use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Lang;

use Carbon\Carbon;

trait Account{

    public function credentials(Request $req, $type = 'login'){
        $config = property_exists($this, 'credentials') ? $this->credentials : array(
            'login' => [
                $this->username(),
                'password'
            ],
            'reset' => [
                'email', 'password', 'password_confirmation', 'token'
            ],
            'send_email' => [
                'email',
            ]
        );
        return $req->only($config[$type]);
    }

    public function rules($type = 'login'){
        $config = property_exists($this, 'rules') ? $this->rules : array(
            'login' => [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ],
            'reset' =>  [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|confirmed|min:8',
            ],
            'send_email' => [
                'email' => 'required|email'
            ],
            'confirm' => [
                'password' => 'required|password',
            ]
        );
        return $config[$type];
    }

    protected function validationErrorMessages(){
        return [];
    }

    // AUTHENTICATE USER
    public function login(Request $request){
        $request->validate($this->rules('login'));
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request){
        return $this->guard()->attempt(
            $this->credentials($request, 'login'),
            $request->filled('remember')
        );
    }

    protected function sendLoginResponse(Request $request){
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);
        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }
        return $request->wantsJson() ? new Response('', 204) : redirect()->intended($this->redirectPath());
    }

    protected function attemptOtp(Request $request){
        $request->validate([config('rbac.otp_input_name') => 'required']);
        $ga = new GoogleAuthenticator();
        if (!$ga->verifyCode($this->guard()->user()->otp_secret, $request->input(config('rbac.otp_input_name')))) {
            return $this->sendFailedOtpResponse($request);
        }
        $request->session()->put(config('rbac.otp_session_identifier'), time());
        return $request->wantsJson() ? new Response('', 204) : redirect()->intended($this->redirectPath());
    }
    
    protected function authenticated(Request $request, $user){
        $user->update([
            'last_login_at' => Carbon::now()->timestamp,
            'last_login_ip' => $request->getClientIp()
        ]);
    }

    protected function sendFailedLoginResponse(Request $request){
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    protected function sendFailedOtpResponse(Request $request){
        throw ValidationException::withMessages(['otp' => [config('rbac.otp_failed_response')],]);
    }

    public function username(){
        return 'username';
    }

    public function logout(Request $request){
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($response = $this->loggedOut($request)) {
            return $response;
        }
        return $request->wantsJson() ? new Response('', 204) : redirect('/');
    }

    protected function loggedOut(Request $request){
        //
    }

    protected function guard(){
        return Auth::guard();
    }


    // CONFIRM PASSWORD
    public function confirm(Request $request){
        $request->validate($this->rules('confirm'), $this->validationErrorMessages());
        $this->resetPasswordConfirmationTimeout($request);
        return $request->wantsJson() ? new Response('', 204) : redirect()->intended($this->redirectPath());
    }

    protected function resetPasswordConfirmationTimeout(Request $request){
        $request->session()->put('rbac.password_confirmed_at', time());
    }


    // REDIRECT USER
    public function redirectPath(){
        $account_id = Auth::user()->account_id;
        return route('rbac.account.switch',['account_id'=>$account_id]) ?? '/home';
    }


    // RESET PASSWORD
    public function reset(Request $request){
        $request->validate($this->rules('reset'), $this->validationErrorMessages());
        $response = $this->broker()->reset(
            $this->credentials($request, 'reset'),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );
        return $response == Password::PASSWORD_RESET ? $this->sendResetResponse($request, $response) : $this->sendResetFailedResponse($request, $response);
    }

    protected function resetPassword($user, $password){
        $user->password = $password;
        $user->save();
        event(new PasswordReset($user));
        $this->guard()->login($user);
    }

    protected function sendResetResponse(Request $request, $response){
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }
        return redirect($this->redirectPath())->with('status', trans($response));
    }

    protected function sendResetFailedResponse(Request $request, $response){
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }
        return redirect()->back()->withInput($request->only('email'))->withErrors(['email' => trans($response)]);
    }

    public function broker(){
        return Password::broker();
    }

    // SEND RESET PASSWORD EMAIL
    public function sendResetLinkEmail(Request $request){
        $request->validate($this->rules('send_email'), $this->validationErrorMessages());
        $response = $this->broker()->sendResetLink(
            $this->credentials($request, 'send_email')
        );
        return $response == Password::RESET_LINK_SENT ? $this->sendResetLinkResponse($request, $response) : $this->sendResetLinkFailedResponse($request, $response);
    }

    protected function sendResetLinkResponse(Request $request, $response){
        return $request->wantsJson() ? new JsonResponse(['message' => trans($response)], 200) : back()->with('status', trans($response));
    }

    protected function sendResetLinkFailedResponse(Request $request, $response){
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }
        return back()->withInput($request->only('email'))->withErrors(['email' => trans($response)]);
    }

    // THROTTLE LOGIN
    protected function hasTooManyLoginAttempts(Request $request){
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts()
        );
    }

    protected function incrementLoginAttempts(Request $request){
        $this->limiter()->hit(
            $this->throttleKey($request),
            $this->decayMinutes() * 60
        );
    }

    protected function sendLockoutResponse(Request $request){
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );
        throw ValidationException::withMessages([
            $this->username() => [Lang::get('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    protected function clearLoginAttempts(Request $request){
        $this->limiter()->clear($this->throttleKey($request));
    }

    protected function fireLockoutEvent(Request $request){
        event(new Lockout($request));
    }

    protected function throttleKey(Request $request){
        return Str::lower($request->input($this->username())) . '|' . $request->ip();
    }

    protected function limiter(){
        return app(RateLimiter::class);
    }

    public function maxAttempts(){
        return config('rbac.login_max_attempts') ?? 5;
    }

    public function decayMinutes(){
        return config('rbac.login_decay') ?? 30;
    }
}
