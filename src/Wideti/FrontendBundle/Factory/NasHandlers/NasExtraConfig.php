<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Entity\AccessPointExtraConfig;

interface NasExtraConfig
{
    /**
     * @param AccessPointExtraConfig $extraConfig
     * @return mixed
     */
    public function setExtraConfig($extraConfig);
}