<?php

namespace Wideti\DomainBundle\Service\Policy;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\Elasticsearch\Policy\PolicyRepository;

class GetPolicyServiceImp implements GetPolicyService
{
    /**
     * @var PolicyRepository
     */
    private $policyRepository;

    /**
     * GetPolicyServiceImp constructor.
     * @param PolicyRepository $policyRepository
     */
    public function __construct(PolicyRepository $policyRepository)
    {
        $this->policyRepository = $policyRepository;
    }

    public function getByGuestMacAddress(Client $client, $guestMacAddress)
    {
        return $this->policyRepository->getLastPolicyByGuestMacAddress($client, $guestMacAddress);
    }
}