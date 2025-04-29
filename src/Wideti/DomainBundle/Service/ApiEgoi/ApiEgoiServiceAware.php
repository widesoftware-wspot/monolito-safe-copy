<?php

namespace Wideti\DomainBundle\Service\ApiEgoi;

use Wideti\DomainBundle\Service\ApiEgoi\ApiEgoiService;

/**
 *
 * Usage: - [ setApiEgoiService, ["@core.service.api_egoi"] ]
 */
trait ApiEgoiServiceAware
{
    /**
     * @var ApiEgoiService
     */
    protected $apiEgoiService;

    public function setApiEgoiService(ApiEgoiService $service)
    {
        $this->apiEgoiService = $service;
    }
}
