<?php namespace Mchuluq\Larv\Rbac;

use Carbon\Carbon;
use Illuminate\Auth\EloquentUserProvider as BaseUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Mchuluq\Larv\Rbac\Helpers\DeviceHelper;
use Mchuluq\Larv\Rbac\Helpers\IpHelper;

class UserProvider extends BaseUserProvider{
    
    public function retrieveByToken($identifier, $token){
        $model = $this->getModelById($identifier);
        if (!$model) {
            return null;
        }
        $rememberTokens = $model->rememberTokens()->where('expires_at', '>', Carbon::now())->get();
        foreach ($rememberTokens as $rememberToken) {
            if (hash_equals($rememberToken->token, $token)) {
                $request = request();
                $current_ip = IpHelper::getRealIp($request);
                
                // Track IP change
                if ($rememberToken->ip_address !== $current_ip) {
                    $rememberToken->trackIpChange($current_ip);
                } else {
                    $rememberToken->update(['last_used_at' => Carbon::now()]);
                }                
                return $model;
            }
        }
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token){
        // Override default behavior - kita tidak update, tapi add/replace
        $request = request();
        $real_ip = IpHelper::getRealIp($request);
        $fingerprint = DeviceHelper::getSimpleFingerprint($request);
        
        // Cari token untuk device ini berdasarkan user agent
        $existingToken = $user->rememberTokens()->where('device_fingerprint', $fingerprint)->where('expires_at', '>', Carbon::now())->first();
        
        if ($existingToken) {
            // Update token yang sudah ada untuk device ini
            $existingToken->update([
                'token' => $token,
                'expires_at' => Carbon::now()->addDays(30), // sesuaikan durasi
                'last_used_at' => Carbon::now(),
                'ip_address' => $real_ip,
                'user_agent' => $request->header('user-agent'),
                'device_fingerprint' => $fingerprint,
            ]);
        } else {
            // Buat token baru untuk device baru
            $this->addRememberToken($user->getAuthIdentifier(), $token, 43200); // 30 hari
        }
    }

    public function addRememberToken($identifier, $value, $expire){
        $model = $this->getModelById($identifier);
        $request = request();
        if(!$model) {
            return;
        };
        $this->enforceDeviceLimit($model);
        $model->rememberTokens()->create([
            'token' => $value,
            'ip_address' => IpHelper::getRealIp($request),
            'user_agent' => $request->header('user-agent'),
            'expires_at' => Carbon::now()->addMinutes($expire),
            'last_used_at' => Carbon::now(),
            'device_fingerprint' => DeviceHelper::getSimpleFingerprint($request),
        ]);
    }

    public function replaceRememberToken($identifier, $token, $newToken, $expire){
        $model = $this->getModelById($identifier);
        $request = request();
        if(!$model) {
            return;
        }
        $model->rememberTokens()->where('token', $token)->update([
            'token' => $newToken,
            'ip_address' => IpHelper::getRealIp($request),
            'user_agent' => $request->header('user-agent'),
            'expires_at' => Carbon::now()->addMinutes($expire),
            'last_used_at' => Carbon::now(),
            'device_fingerprint' => DeviceHelper::getSimpleFingerprint($request),
        ]);
    }

    public function deleteRememberToken($identifier, $token){
        $model = $this->getModelById($identifier);        
        if (!$model) {
            return;
        }        
        $tokenModel = $model->rememberTokens()->where('token', $token)->first();        
        if ($tokenModel) {
            $tokenModel->delete();
        }
    }

    public function purgeRememberTokens($identifier, $expired = false){
        $model = $this->getModelById($identifier);
        if ($model) {
            $query = $model->rememberTokens();
            if ($expired) {
                $query->where('expires_at', '<', Carbon::now());
            }
            $query->delete();
        }

        $model = $this->getModelById($identifier);
        if (!$model) {
            return;
        }        
        $query = $model->rememberTokens();
        if ($expired) {
            $query->where('expires_at', '<', Carbon::now());
        }
        $query->delete();
    }

    protected function getModelById($identifier){
        $model = $this->createModel();
        return $model->where([$model->getAuthIdentifierName()=>$identifier,'active'=>true])->first();
    }

    protected function enforceDeviceLimit($user){
        $maxDevices = config('rbac.max_devices', 5);
        if(!config('rbac.enforce_limit', false)){
            return;
        }
        // Count active tokens
        $activeTokens = $user->rememberTokens()->where('expires_at', '>', Carbon::now())->orderBy('last_used_at', 'asc')->get();
        // If at or over limit, remove oldest devices
        while ($activeTokens->count() >= $maxDevices) {
            $oldest = $activeTokens->first();
            if ($oldest) {
                $oldest->delete();
                $activeTokens = $activeTokens->slice(1); // Remove from collection
            } else {
                break;
            }
        }
    }
}
