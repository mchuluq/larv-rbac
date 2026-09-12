<?php namespace Mchuluq\Larv\Rbac\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memutus seluruh jalur akses yang sudah terbit untuk satu user: session yang
 * masih hidup, remember token (perangkat), token OAuth, dan api token statis.
 *
 * Trait ini menempel pada lifecycle SoftDeletes, jadi tidak perlu dipanggil
 * manual: begitu user di-delete (mekanisme "disable") seluruh aksesnya dicabut,
 * dan begitu di-restore ("enable") cache rbac dibersihkan supaya permission
 * dibangun ulang. Soft delete sendiri sudah menutup pintu masuk lewat global
 * scope SoftDeletingScope - setiap pencarian user (login, session, remember
 * token, password grant) otomatis melewatkan baris yang ter-delete - sehingga
 * pencabutan di sini sifatnya membersihkan jejak, bukan satu-satunya pengaman.
 */
trait RevokesAccess {

    /**
     * Dipanggil otomatis oleh Eloquent::bootTraits().
     */
    public static function bootRevokesAccess(){
        static::deleted(function($user){
            $user->revokeAccess();
        });
        static::restored(function($user){
            $user->clearRbacStorage();
        });
    }

    /**
     * @return array{sessions:int,devices:int,oauth_tokens:int}
     */
    public function revokeAccess(): array{
        $summary = $this->countAccess();

        if($query = $this->sessionQuery()){
            $query->delete();
        }
        $this->rememberTokens()->delete();
        $this->revokeOauthTokens();
        $this->forgetApiToken();
        $this->clearRbacStorage();

        return $summary;
    }

    /**
     * @return array{sessions:int,devices:int,oauth_tokens:int}
     */
    public function countAccess(): array{
        return [
            'sessions' => ($query = $this->sessionQuery()) ? $query->count() : 0,
            'devices' => $this->rememberTokens()->count(),
            'oauth_tokens' => ($query = $this->oauthTokenQuery()) ? $query->where('revoked',false)->count() : 0,
        ];
    }

    /**
     * Session hanya bisa dihapus per user kalau disimpan di database. Pada
     * driver lain global scope soft delete yang menahan session lama.
     */
    protected function sessionQuery(){
        if(config('session.driver') !== 'database'){
            return null;
        }
        return DB::table(config('session.table','sessions'))->where('user_id',$this->getAuthIdentifier());
    }

    /**
     * Passport bersifat opsional - paket ini tidak mensyaratkannya.
     *
     * Relasi $user->tokens() dari HasApiTokens sengaja tidak dipakai:
     * getProviderName() di dalamnya hanya mengenali provider dengan driver
     * 'eloquent', sehingga pada aplikasi yang memakai provider 'rbac-user'
     * relasi itu selalu melempar LogicException. Query langsung ke model token
     * juga memastikan seluruh token user dicabut, bukan hanya milik satu
     * provider.
     */
    protected function oauthTokenQuery(){
        if(!class_exists(\Laravel\Passport\Passport::class)){
            return null;
        }
        return \Laravel\Passport\Passport::token()->newQuery()->where('user_id',$this->getAuthIdentifier());
    }

    protected function revokeOauthTokens(){
        if(!$query = $this->oauthTokenQuery()){
            return;
        }
        $passport = \Laravel\Passport\Passport::class;

        $passport::authCode()->newQuery()->where('user_id',$this->getAuthIdentifier())->where('revoked',false)->update(['revoked'=>true]);

        $token_ids = $query->where('revoked',false)->pluck('id');
        if($token_ids->isEmpty()){
            return;
        }
        $passport::token()->newQuery()->whereIn('id',$token_ids)->update(['revoked'=>true]);
        $passport::refreshToken()->newQuery()->whereIn('access_token_id',$token_ids)->update(['revoked'=>true]);
    }

    /**
     * Kolom api_token bersifat opsional; kalau aplikasi tidak memakainya
     * nilainya null dan tidak ada yang perlu dikosongkan. Pada force delete
     * barisnya sudah hilang, jadi jangan disimpan ulang - save() pada model
     * yang exists=false akan menjadi INSERT.
     */
    protected function forgetApiToken(){
        if(!$this->exists || is_null($this->getAttribute('api_token'))){
            return;
        }
        $this->forceFill(['api_token'=>null])->saveQuietly();
    }

    /**
     * Cache rbac (permission + account aktif) ada di cache store, sedangkan
     * pemutusan session jauh lebih penting - jangan sampai cache bermasalah
     * lalu membatalkan seluruh proses.
     */
    public function clearRbacStorage(){
        try {
            $this->storage()->clear();
        } catch (\Throwable $e) {
            Log::warning('rbac: gagal membersihkan storage user '.$this->getAuthIdentifier().': '.$e->getMessage());
        }
    }
}
