<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Repository\SMSBillingControlRepository;

class FilterService
{
    /**
     * @var SMSBillingControlRepository
     */
    private $SMSBillingControlRepository;


    /**
     * FilterService constructor.
     * @param SMSBillingControlRepository $SMSBillingControlRepository
     */
    public function __construct(SMSBillingControlRepository $SMSBillingControlRepository)
    {
        $this->SMSBillingControlRepository = $SMSBillingControlRepository;
    }

    /**
     * @param $condition
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function filter($condition)
    {
        return $this->SMSBillingControlRepository->getDataByFilter($condition);
    }
}