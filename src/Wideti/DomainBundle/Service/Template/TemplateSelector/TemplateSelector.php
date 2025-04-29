<?php

namespace Wideti\DomainBundle\Service\Template\TemplateSelector;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\FrontendBundle\Factory\Nas;

interface TemplateSelector
{
    /**
     * @param Nas $nas
     * @param Client $client
     * @param Campaign $campaign
     * @return Template
     */
    public function select(Nas $nas = null, Client $client, Campaign $campaign = null);
}
