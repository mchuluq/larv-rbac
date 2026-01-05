<?php

namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mchuluq\Larv\Rbac\Helpers\DeviceHelper;
use Mchuluq\Larv\Rbac\Helpers\IpHelper;

// use Mchuluq\Larv\Rbac\Models\User as User

class RememberToken extends Model{
    
    protected $fillable = [
        'token', 'user_id', 'expires_at','user_agent','ip_address','last_used_at',
        'device_fingerprint','device_type','ip_history'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'ip_history' => 'array'
    ];

    public function getActiveSessionAttribute(){
        if (config('session.driver') !== 'database') {
            return null;
        }
        return $this->session->where('user_id', $this->user_id)->first();
    }

    public function session(){
        return $this->hasMany(Session::class,'remember_token','token');
    }

    public function hasActiveSession(){
        return $this->active_session !== null;
    }

    public function getSessionLastActivityAttribute(){
        $session = $this->active_session;        
        if (!$session) {
            return null;
        }
        return \Carbon\Carbon::createFromTimestamp($session->last_activity);
    }

    public function setUserAgentAttribute($value){
        $this->attributes['user_agent'] = $value;
        $this->attributes['device_type'] = DeviceHelper::getDeviceType($value);
    }

    public function getDeviceNameAttribute(){
        return DeviceHelper::getDeviceName($this->user_agent);
    }
    public function getDeviceInfoAttribute(){
        return DeviceHelper::getDeviceInfo($this->user_agent);
    }
    public function getDeviceIconAttribute(){
        return DeviceHelper::getDeviceIcon($this->user_agent);
    }

    public function getIpInfoAttribute(){
        $ip = new IpHelper($this->ip_address);
        if($ip->isLocal()){
            return 'local';
        }
        return $ip->lookup();
    }

    public function getBrowserAttribute(){
        return $this->browser_name ?? 'Unknown';
    }

    public function isCurrentDevice(){
        // Method 1: Token matching (PRIMARY - most accurate)
        if ($this->isCurrentDeviceByToken()) {
            return true;
        }
        // Method 2: Fingerprint matching (FALLBACK - for UX when cookie unavailable)
        return $this->isCurrentDeviceByFingerprint();
    }

    public function isCurrentDeviceByToken(){
        $request = request();
        $key = Auth::getRecallerName();
        $cookie = $request->cookie($key);
        if (!$cookie) {
            return false;
        }
        // Parse cookie format: user_id|token|password_hash
        $segments = explode('|', $cookie);
        if (count($segments) !== 3) {
            return false;
        }        
        list($userId, $cookieToken, $passwordHash) = $segments;
        // Compare tokens (tokens are NOT hashed in cookie)
        return hash_equals($this->token, $cookieToken);
    }

    public function isCurrentDeviceByFingerprint(){
        $request = request();
        $currentFingerprint = DeviceHelper::getSimpleFingerprint($request);    
        return $this->device_fingerprint === $currentFingerprint;
    }

    public function getCurrentDeviceConfidence(){
        $tokenMatch = $this->isCurrentDeviceByToken();
        $fingerprintMatch = $this->isCurrentDeviceByFingerprint();    
        if ($tokenMatch && $fingerprintMatch) {
            return [
                'is_current' => true,
                'confidence' => 'high',
                'method' => 'token_and_fingerprint',
                'note' => 'Token dan fingerprint cocok - sangat yakin ini device yang sama',
            ];
        }        
        if ($tokenMatch && !$fingerprintMatch) {
            return [
                'is_current' => true,
                'confidence' => 'high',
                'method' => 'token_only',
                'note' => 'Token cocok tapi fingerprint berbeda - kemungkinan browser update',
            ];
        }        
        if (!$tokenMatch && $fingerprintMatch) {
            return [
                'is_current' => false,
                'confidence' => 'low',
                'method' => 'fingerprint_only',
                'note' => 'Hanya fingerprint cocok - suspicious, cookie mungkin dicuri/dihapus',
                'suspicious' => true,
            ];
        }        
        return [
            'is_current' => false,
            'confidence' => 'none',
            'method' => 'none',
            'note' => 'Bukan device ini',
        ];
    }

    public function hasIpChanged(){
        $request = request();
        $currentIp = IpHelper::getRealIp($request);        
        return $this->ip_address !== $currentIp;
    }

    public function trackIpChange($newIp){
        $history = $this->ip_history ?? [];
        
        // Add new IP dengan timestamp
        $history[] = [
            'ip' => $newIp,
            'detected_at' => now()->toDateTimeString(),
        ];
        
        // Keep only last 10 IPs
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        
        $this->update([
            'ip_address' => $newIp,
            'ip_history' => $history,
        ]);
    }

    public function scopeActive($query){
        return $query->where('expires_at', '>', now());
    }
    public function scopeExpired($query){
        return $query->where('expires_at', '<=', now());
    }
    public function scopeDeviceType($query, $deviceType){
        return $query->where('device_type', $deviceType);
    }
    
}
