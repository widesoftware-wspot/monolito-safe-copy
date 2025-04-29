<?php

namespace Wideti\DomainBundle\Helpers;

class DeviceHelper
{
    const MACINTOSH = "Macintosh";
    const IPHONE    = "iPhone";
    const IPAD      = "iPad";

    public static function checkAppleUser($userAgent)
    {
        if (strstr($userAgent, self::MACINTOSH)
            || strstr($userAgent, self::IPHONE)
            || strstr($userAgent, self::IPAD)
        ) {
            return true;
        }
        return false;
    }

    public static function getAccessDataInfo($userAgent)
    {
        $os      = 'Unknown';
        $device  = 'Mobile';

        if (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif ((preg_match('/^(?=.*mac os x)(?:(?!windows).)+$/i', $userAgent))
            || (preg_match('/^(?=.*macintosh)(?:(?!windows).)+$/i', $userAgent))) {
            $os = 'Mac OSX';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
            if (preg_match('/NT 6.2/i', $userAgent)) {
                $os .= ' 8';
            } elseif (preg_match('/NT 6.3/i', $userAgent)) {
                $os .= ' 8.1';
            } elseif (preg_match('/NT 6.1/i', $userAgent)) {
                $os .= ' 7';
            } elseif (preg_match('/NT 6.0/i', $userAgent)) {
                $os .= ' Vista';
            } elseif (preg_match('/NT 5.1/i', $userAgent)) {
                $os .= ' XP';
            }
        }

        // OS
        if ($os == 'Linux' && strpos($userAgent, 'Android')) {
            $os = "Android";
        } elseif ($os == 'Mac OSX' && strpos($userAgent, 'Mobile')) {
            $os = "iOS";
        }

        // DEVICE
        if (self::accessIsPc($userAgent)) {
            $device = "PC";
        }

        return [
            'os'     => $os,
            'device' => $device
        ];
    }

    private static function accessIsPc($userAgent)
    {
        $devicePcs = [
            'Windows',
            'Macintosh',
            'Linux x86_64',
            'Linux i686',
            'Ubuntu',
        ];

        foreach ($devicePcs as $pc) {
            if (strpos($userAgent, $pc) !== false) {
                return true;
            }
        }

        return false;
    }
}
