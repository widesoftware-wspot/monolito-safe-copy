<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\SMSBillingControlRepository;

class BillingExistsService
{
    /**
     * @var SMSBillingControlRepository
     */
    private $SMSBillingControlRepository;

    /**
     * BillingExistsService constructor.
     * @param SMSBillingControlRepository $SMSBillingControlRepository
     */
    public function __construct(SMSBillingControlRepository $SMSBillingControlRepository)
    {
        $this->SMSBillingControlRepository = $SMSBillingControlRepository;
    }

    public function check(Client $client, $closingDateStart, $closingDateEnd)
    {
        return $this->SMSBillingControlRepository->checkIfSMSBillingExists(
            $client,
            $closingDateStart,
            $closingDateEnd
        );
    }
}