<?php

namespace Mchuluq\Larv\Rbac\Http\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mchuluq\Larv\Rbac\Traits\HasParameters;

class LinkSessionToDeviceToken {

    public function handle($request, Closure $next){
        if(Auth::check() && config('session.driver') == 'database'){
            $this->ensureSessionLinked($request);
        }
        return $next($request);
    }

    protected function ensureSessionLinked($request){
        $session_id = session()->getId();
        $user = Auth::user();

        // Get current session from database
        $session = DB::table(config('session.table', 'sessions'))->where('id', $session_id)->first();
        // If session already linked, skip
        if ($session && $session->remember_token) {
            return;
        }
        // Try to find matching device token
        $recaller = $request->cookie(Auth::getRecallerName());
        if ($recaller) {
            $segments = explode('|', $recaller);            
            if (count($segments) === 3) {
                $token = $segments[1];                
                if ($token) {
                    DB::table(config('session.table', 'sessions'))->where('id', $session_id)->update(['remember_token' => $token]);
                }
            }
        }
    }
}