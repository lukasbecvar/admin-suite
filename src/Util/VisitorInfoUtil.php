<?php

namespace App\Util;

/**
 * Class VisitorInfoUtil
 *
 * Util for get visitor info
 *
 * @package App\Util
 */
class VisitorInfoUtil
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Get visitor IP address
     *
     * @return string|null The current visitor IP address
     */
    public function getIP(): ?string
    {
        $ipAddress = null;

        // check client IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }

        // check forwarded IP (get IP from cloudflare visitors)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $ipAddress == null) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Unknown';
        }

        // get ip address from remote addr
        if ($ipAddress == null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        // escape ip address
        if ($ipAddress !== null) {
            $ipAddress = $this->securityUtil->escapeString($ipAddress);
        }

        return $ipAddress ?? 'Unknown';
    }

    /**
     * Get user agent
     *
     * @return string|null The user agent
     */
    public function getUserAgent(): ?string
    {
        // get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        /** @var string $browserAgent return user agent */
        $browserAgent = $userAgent !== null ? $userAgent : 'Unknown';

        // escape user agent
        $browserAgent = $this->securityUtil->escapeString($browserAgent);

        return $browserAgent;
    }

    /**
     * Get a short version of the browser name
     *
     * @param string $userAgent The user agent string
     *
     * @return string|null The short browser name
     */
    public function getBrowserShortify(string $userAgent): ?string
    {
        // identify common browsers using switch statement
        switch (true) {
            case preg_match('/MSIE (\d+\.\d+);/', $userAgent):
            case str_contains($userAgent, 'MSIE'):
                $output = 'Internet Explore';
                break;
            case preg_match('/Chrome[\/\s](\d+\.\d+)/', $userAgent):
                $output = 'Chrome';
                break;
            case preg_match('/Edge\/\d+/', $userAgent):
                $output = 'Edge';
                break;
            case preg_match('/Firefox[\/\s](\d+\.\d+)/', $userAgent):
            case str_contains($userAgent, 'Firefox/96'):
                $output = 'Firefox';
                break;
            case preg_match('/Safari[\/\s](\d+\.\d+)/', $userAgent):
                $output = 'Safari';
                break;
            case str_contains($userAgent, 'UCWEB'):
            case str_contains($userAgent, 'UCBrowser'):
                $output = 'UC Browser';
                break;
            case str_contains($userAgent, 'Iceape'):
                $output = 'IceApe Browser';
                break;
            case str_contains($userAgent, 'maxthon'):
                $output = 'Maxthon Browser';
                break;
            case str_contains($userAgent, 'konqueror'):
                $output = 'Konqueror Browser';
                break;
            case str_contains($userAgent, 'NetFront'):
                $output = 'NetFront Browser';
                break;
            case str_contains($userAgent, 'Midori'):
                $output = 'Midori Browser';
                break;
            case preg_match('/OPR[\/\s](\d+\.\d+)/', $userAgent):
            case preg_match('/Opera[\/\s](\d+\.\d+)/', $userAgent):
                $output = 'Opera';
                break;
            default:
                // if not found, check user agent length
                if (str_contains($userAgent, ' ') || strlen($userAgent) >= 39) {
                    $output = 'Unknown';
                } else {
                    $output = $userAgent;
                }
        }

        return $output;
    }

    /**
     * Get the visitor operating system
     *
     * @param string $userAgent The user agent string
     *
     * @return string|null The operating system
     */
    public function getOs(string $userAgent = 'self'): ?string
    {
        $os = 'Unknown OS';

        // get user agent
        if ($userAgent == 'self') {
            $userAgent = $this->getUserAgent();
        }

        // OS list
        $osArray = array (
            '/windows nt 5.2/i'     =>  'Windows Server_2003',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/win16/i'              =>  'Windows 3.11',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/blackberry/i'         =>  'BlackBerry',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/SMART-TV/i'           =>  'Smart TV',
            '/windows/i'            =>  'Windows',
            '/iphone/i'             =>  'Mac IOS',
            '/android/i'            =>  'Android',
            '/webos/i'              =>  'Mobile',
            '/ubuntu/i'             =>  'Ubuntu',
            '/linux/i'              =>  'Linux',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad'
        );

        // find os
        foreach ($osArray as $regex => $value) {
            if ($userAgent !== null && preg_match($regex, $userAgent)) {
                $os = $value;
                break;
            }
        }

        return $os;
    }
}
