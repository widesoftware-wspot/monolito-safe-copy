<?php

namespace Wideti\DomainBundle\Service\Contract;

use Wideti\DomainBundle\Repository\ContractUserRepository;

class GetUserThatConfirmedSmsCostContractService
{
    /**
     * @var ContractUserRepository
     */
    private $contractUserRepository;

    /**
     * GetUserThatConfirmedSmsCostContractService constructor.
     * @param ContractUserRepository $contractUserRepository
     */
    public function __construct(ContractUserRepository $contractUserRepository)
    {
        $this->contractUserRepository = $contractUserRepository;
    }

    /**
     * @param $user
     * @return mixed
     */
    public function get($user)
    {
        return $this->contractUserRepository->getByUser($user);
    }
}