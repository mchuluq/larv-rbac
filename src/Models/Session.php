<?php namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Session extends Model{
    
    protected $appends = ['expires_at'];

    public function isExpired(){
        return $this->last_activity < Carbon::now()->subMinutes(config('session.lifetime'))->getTimestamp();
    }

    public function getExpiresAtAttribute(){
        return Carbon::createFromTimestamp($this->last_activity)->addMinutes(config('session.lifetime'))->toDateTimeString();
    }

    public static function active_sessions(){
        $session_lifetime = (int) (5 * 60); // last 5 minutes
        return Session::whereRaw(DB::raw("(UNIX_TIMESTAMP() - last_activity) <= $session_lifetime"))->count();
    }

    public function device(){
        return $this->belongsTo(RememberToken::class,'remember_token','token');
    }
}