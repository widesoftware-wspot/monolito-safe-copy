<?php

namespace Wideti\DomainBundle\Service\RedirectUrl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

interface RedirectUrlService
{
    /**
     * @param Nas $nas
     * @param Client $client
     * @return string
     */
    public function getRedirectUrl(Nas $nas = null, Client $client);
}
