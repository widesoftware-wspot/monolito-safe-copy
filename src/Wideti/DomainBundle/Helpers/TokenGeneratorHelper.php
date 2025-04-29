<?php

namespace Wideti\DomainBundle\Helpers;

class TokenGeneratorHelper
{
    public static function getUniqueToken()
    {
        $available = "abcdefghijklmnopqrstuvxwyz0123456789";
        $size = 7;
        $prefix = '';
        for ($i=0; $i<$size; $i++) {
            $prefix .= $available[mt_rand(0, strlen($available-1))];
        }
        return uniqid($prefix);
    }
}
