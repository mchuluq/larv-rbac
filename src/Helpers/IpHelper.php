<?php namespace Mchuluq\Larv\Rbac\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use Symfony\Component\HttpFoundation\IpUtils;

class IpHelper{

    protected $ip_address;
    protected $cache_lifetime = 86400;
    protected $params = [
        'fields' => 24899583, //equals to : status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,isp,org,as,asname,mobile,proxy,hosting,query
    ];

    protected $private_ranges = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
    ];

    public function __construct($ip_address=null){
        $this->ip_address = ($ip_address) ? $ip_address : self::getRealIp(request());
    }

    public function isLocal(){
        return IpUtils::checkIp($this->ip_address, $this->private_ranges);
    }

    public function lookup(){
        $endpoint = "http://ip-api.com/json/".$this->ip_address;
        $param = http_build_query($this->params);
        $url = $endpoint.'?'.$param;
        return Cache::remember('ip-api-lookup-'.$endpoint,$this->cache_lifetime, function() use ($url) {
            return Http::acceptJson()->withOptions(['verify' => false])->get($url)->throw()->json();
        });
    }

    /**
     * Dapatkan real IP dari user, bahkan di balik proxy
     */
    public static function getRealIp(Request $request): string{
        // Priority order untuk header IP
        $headers = [
            'CF-Connecting-IP',     // Cloudflare
            'True-Client-IP',       // Cloudflare Enterprise
            'X-Real-IP',            // Nginx
            'X-Forwarded-For',      // Standard proxy header
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
        ];
        foreach ($headers as $header) {
            $ip = $request->header($header);
            if ($ip) {
                // X-Forwarded-For bisa berisi multiple IP: "client, proxy1, proxy2"
                // Ambil yang pertama (client IP)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validasi IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        // Fallback ke IP() biasa
        return $request->ip();
    }

    /**
     * Dapatkan semua IP yang terdeteksi (untuk debugging)
     */
    public static function getAllIps(Request $request): array{
        return [
            'request_ip' => $request->ip(),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'x_real_ip' => $request->header('X-Real-IP'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'remote_addr' => $request->server('REMOTE_ADDR'),
        ];
    }

    public static function resolve():string{
        return self::getRealIp(request());
    }

}
