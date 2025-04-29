<?php

namespace Wideti\DomainBundle\Helpers\Cookie;

use Wideti\DomainBundle\Exception\CookieDisabledException;

class CookieHelper
{
    const COOKIE_KEY_CHECK_ENABLE = "isCookieEnable";
    const COOKIE_VALUE_CHECK_ENABLE = true;

    private function __construct()
    {
    }

    /**
     * @return bool
     * @throws CookieDisabledException
     */
    public static function checkCookieEnable()
    {
        if (!isset($_COOKIE['PHPSESSID'])) {
            throw new CookieDisabledException("Please enable the cookie in your browser to use Mambo Wifi");
        }
        return true;
    }
}
