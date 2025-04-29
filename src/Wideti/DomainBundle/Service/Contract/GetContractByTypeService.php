<?php

namespace Wideti\DomainBundle\Service\Contract;

use Wideti\DomainBundle\Repository\ContractRepository;

/**
 * Class GetContractService
 * @package Wideti\DomainBundle\Service\Contract
 */
class GetContractByTypeService
{
    /**
     * @var ContractRepository
     */
    private $contractRepository;

    /**
     * GetContractService constructor.
     * @param ContractRepository $contractRepository
     */
    public function __construct(ContractRepository $contractRepository)
    {
        $this->contractRepository = $contractRepository;
    }

    public function get($type)
    {
        return $this->contractRepository->getContractByType($type);
    }
}