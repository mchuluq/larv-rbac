<?php namespace Mchuluq\Larv\Rbac\Helpers;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Http\Request;

class DeviceHelper{

    /**
     * Get DeviceDetector instance
     */
    protected static function getDetector(Request|string $request): DeviceDetector{
        $userAgent = (is_string($request)) ? $request : $request->header('user-agent') ?? '';        
        $detector = new DeviceDetector($userAgent);        
        // Optional: Set caching
        $detector->setCache(new \DeviceDetector\Cache\LaravelCache());
        $detector->parse();        
        return $detector;
    }

    /**
     * Generate device fingerprint yang konsisten
     * Gunakan kombinasi device info dari Matomo
     */
    public static function getFingerprint(Request|string $request): string{
        $detector = self::getDetector($request);

        // Jangan include bot dalam fingerprint
        if ($detector->isBot()) {
            return hash('sha256', 'bot-' . $request->header('user-agent'));
        }
        
        $components = [
            $detector->getOs('name') ?? 'unknown',
            $detector->getOs('version') ?? 'unknown',
            $detector->getClient('name') ?? 'unknown',
            // Jangan include client version karena auto-update browser
            // $detector->getClient('version'),
            $detector->getDeviceName() ?? 'unknown',
            $detector->getBrandName() ?? 'unknown',
            $detector->getModel() ?? 'unknown',
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Generate simple fingerprint (untuk backward compatibility)
     * Hanya dari user agent
     */
    public static function getSimpleFingerprint(Request $request): string{
        $userAgent = $request->header('user-agent') ?? 'unknown';
        return hash('sha256', $userAgent);
    }

    /**
     * Generate device name yang sangat deskriptif
     */
    public static function getDeviceName(Request|string $request): string{
        $detector = self::getDetector($request);        
        if ($detector->isBot()) {
            $botInfo = $detector->getBot();
            return $botInfo['name'] ?? 'Unknown Bot';
        }        
        $parts = [];        
        // Browser
        $client = $detector->getClient();
        if ($client && isset($client['name'])) {
            $browser = $client['name'];
            if (isset($client['version'])) {
                $browser .= ' ' . $client['version'];
            }
            $parts[] = $browser;
        }
        
        // OS
        $os = $detector->getOs();
        if ($os && isset($os['name'])) {
            $osName = $os['name'];
            if (isset($os['version'])) {
                $osName .= ' ' . $os['version'];
            }
            $parts[] = $osName;
        }
        
        // Device Type & Brand
        $deviceType = $detector->getDeviceName() ?? 'Desktop';
        $brand = $detector->getBrandName();
        $model = $detector->getModel();
        
        if ($brand || $model) {
            $deviceInfo = trim(($brand ?? '') . ' ' . ($model ?? ''));
            $parts[] = $deviceInfo . " ($deviceType)";
        } else {
            $parts[] = "($deviceType)";
        }
        
        return implode(' on ', $parts) ?: 'Unknown Device';
    }

    /**
     * Get detailed device information
     */
    public static function getDeviceInfo(Request|string $request): array{
        $detector = self::getDetector($request);        
        $isBot = $detector->isBot();        
        return [
            'is_bot' => $isBot,
            'bot_info' => $isBot ? $detector->getBot() : null,
            
            // Client (Browser) info
            'client' => $detector->getClient(),
            'client_name' => $detector->getClient('name'),
            'client_version' => $detector->getClient('version'),
            'client_type' => $detector->getClient('type'),
            
            // OS info
            'os' => $detector->getOs(),
            'os_name' => $detector->getOs('name'),
            'os_version' => $detector->getOs('version'),
            'os_platform' => $detector->getOs('platform'),
            
            // Device info
            'device_type' => $detector->getDeviceName(),
            'device_brand' => $detector->getBrandName(),
            'device_model' => $detector->getModel(),
            
            // Raw
            'user_agent' => (is_string($request)) ? $request : $request->header('user-agent'),
        ];
    }

    /**
     * Get browser name
     */
    public static function getBrowserName(Request|string $request): string{
        $detector = self::getDetector($request);
        $client = $detector->getClient();
        
        return $client['name'] ?? 'Unknown Browser';
    }

    /**
     * Get OS name
     */
    public static function getOsName(Request|string $request): string{
        $detector = self::getDetector($request);
        $os = $detector->getOs();
        
        if ($os && isset($os['name'])) {
            $osName = $os['name'];
            if (isset($os['version'])) {
                $osName .= ' ' . $os['version'];
            }
            return $osName;
        }
        
        return 'Unknown OS';
    }

    /**
     * Get device type (desktop, smartphone, tablet, etc)
     */
    public static function getDeviceType(Request|string $request): string{
        $detector = self::getDetector($request);        
        return $detector->getDeviceName() ?? 'desktop';
    }

    /**
     * Check if request is from bot
     */
    public static function isBot(Request|string $request): bool{
        $detector = self::getDetector($request);
        return $detector->isBot();
    }

    /**
     * Check if mobile device
     */
    public static function isMobile(Request|string $request): bool{
        $detector = self::getDetector($request);
        return $detector->isMobile();
    }

    /**
     * Check if desktop
     */
    public static function isDesktop(Request|string $request): bool{
        $detector = self::getDetector($request);
        return $detector->isDesktop();
    }

    /**
     * Get icon class untuk device type (untuk UI)
     */
    public static function getDeviceIcon(Request|string $request): string{
        $deviceType = self::getDeviceType($request);        
        $icons = [
            'desktop' => 'fa-desktop',
            'smartphone' => 'fa-mobile-alt',
            'tablet' => 'fa-tablet-alt',
            'tv' => 'fa-tv',
            'car browser' => 'fa-car',
            'smart display' => 'fa-display',
            'camera' => 'fa-camera',
            'portable media player' => 'fa-music',
            'console' => 'fa-gamepad',
        ];
        
        return $icons[strtolower($deviceType)] ?? 'fa-question-circle';
    }
}