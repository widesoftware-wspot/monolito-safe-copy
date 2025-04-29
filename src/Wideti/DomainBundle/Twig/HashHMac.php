<?php

namespace Wideti\DomainBundle\Twig;

class HashHMac extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('hash_hmac', [$this, 'getHash'])
        ];
    }

    public function getHash($data)
    {
        $hash = hash_hmac(
            'sha256',
            $data,
            'vUg3OlOaZmYvCOwl6hxaV4Pk3BxD2k4qwR-UgJRn'
        );

        return $hash;
    }

    public function getName()
    {
        return 'hash_hmac';
    }
}
