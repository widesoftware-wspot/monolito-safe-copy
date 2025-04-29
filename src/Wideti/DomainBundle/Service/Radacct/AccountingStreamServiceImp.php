<?php

namespace Wideti\DomainBundle\Service\Radacct;

use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\AccountingStreamRepository;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamDto;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;
use Wideti\DomainBundle\Service\Radacct\Helper\ParseStreamResultToDtoHelper;

class AccountingStreamServiceImp implements AccountingStreamService
{
    /**
     * @var AccountingStreamRepository
     */
    private $accountingStreamRepository;

    public function __construct(AccountingStreamRepository $accountingStreamRepository)
    {
        $this->accountingStreamRepository = $accountingStreamRepository;
    }

    /**
     * @param AcctStreamFilterDto $filter
     * @return AcctStreamDto
     * @throws ClientNotFoundException
     */
    public function get(AcctStreamFilterDto $filter)
    {
        $result = $this
            ->accountingStreamRepository
            ->findByFilter($filter);

        return ParseStreamResultToDtoHelper::parse($result);
    }
}
