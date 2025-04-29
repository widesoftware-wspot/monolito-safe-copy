<?php

namespace Wideti\DomainBundle\Service\Contract;

use Wideti\DomainBundle\Service\Contract\ContractService;

/**
 * Usage: - [ setContractService, [@core.service.contract] ]
 */
trait ContractServiceAware
{
    /**
     * @var ContractService
     */
    protected $contractService;

    public function setContractService(ContractService $service)
    {
        $this->contractService = $service;
    }
}
