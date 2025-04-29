<?php

namespace Wideti\DomainBundle\Service\Social\Facebook;

class FacebookHelper
{
    const PERMISSION_EMAIL = 'email';
    const PERMISSION_PUBLIC_PROFILE = 'public_profile';
    const PERMISSION_PUBLISH_ACTION = 'publish_actions';

    public static function hasPermission($permissionsArray, $needed)
    {
        foreach ($permissionsArray as $p) {
            if ($p['permission'] === $needed && $p['status'] === 'granted') {
                return true;
            }
        }

        return false;
    }
}
