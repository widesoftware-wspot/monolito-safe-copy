<?php

namespace Wideti\DomainBundle\Service\RadiusPolicy;

use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicy;

interface RadiusPolicyService
{
    /**
     * @param RadiusPolicy $policy
     * @return RadiusPolicy
     */
    public function save(RadiusPolicy $policy);
}
