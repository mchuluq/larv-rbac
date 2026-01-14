<?php namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Session extends Model{
    
    protected $appends = ['expires_at'];

    static $SESSION_LIFETIME = 5 * 60; // last 5 minutes

    public function isExpired(){
        return $this->last_activity < Carbon::now()->subMinutes(config('session.lifetime'))->getTimestamp();
    }

    public function getExpiresAtAttribute(){
        return Carbon::createFromTimestamp($this->last_activity)->addMinutes(config('session.lifetime'))->toDateTimeString();
    }

    public static function active_sessions(){
        return Session::whereRaw(DB::raw("(UNIX_TIMESTAMP() - last_activity) <= ".self::$SESSION_LIFETIME.""))->count();
    }

    public function device(){
        return $this->belongsTo(RememberToken::class,'remember_token','token');
    }

    public static function statistics(){
        return [
            'active_sessions' => Session::whereRaw(DB::raw("(UNIX_TIMESTAMP() - last_activity) <= ".self::$SESSION_LIFETIME.""))->count(),
            'total_sessions' => Session::count(),
            'active_user_sessions' => Session::whereNotNull('user_id')->whereRaw(DB::raw("(UNIX_TIMESTAMP() - last_activity) <= ".self::$SESSION_LIFETIME.""))->count(),
            'total_user_sessions' => Session::whereNotNull('user_id')->count(),
            'active_user_devices' => Session::whereNotNull('user_id')->whereNotNull('remember_token')->whereRaw(DB::raw("(UNIX_TIMESTAMP() - last_activity) <= ".self::$SESSION_LIFETIME.""))->count(),
            'total_user_devices' => Session::whereNotNull('user_id')->whereNotNull('remember_token')->count(),
        ];
    }
}