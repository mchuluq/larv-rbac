<?php namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Mchuluq\Larv\Rbac\Traits\HasParameters;

class CheckAccount {

    use HasParameters;

    public function handle($request, Closure $next, $checkAccount=true){
        if (!$request->user()->storage()->has('rbac.account') && $checkAccount == true) {
            return $this->sendNeedSelectAccount($request);
        }
        return $next($request);
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