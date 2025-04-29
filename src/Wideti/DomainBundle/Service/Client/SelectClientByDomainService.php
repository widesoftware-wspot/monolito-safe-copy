<?php

namespace Wideti\DomainBundle\Service\Client;

interface SelectClientByDomainService
{
    /**
     * @param $domain
     * @return mixed
     */
    public function get($domain);
}
