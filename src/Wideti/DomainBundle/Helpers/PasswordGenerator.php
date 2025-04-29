<?php

namespace Wideti\DomainBundle\Helpers;

class PasswordGenerator
{
    public function generate()
    {
        $isStrong = false;
        $plainPassword = bin2hex(openssl_random_pseudo_bytes(4, $isStrong));

        if (!$isStrong) {
            throw new \RuntimeException('Insecure random bytes generated');
        }
        return $plainPassword;
    }
}
