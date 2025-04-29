<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;

use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

interface AccountingStreamRepository
{
    /**
     * @param AcctStreamFilterDto $filterDto
     * @return array
     * @throws ClientNotFoundException
     */
    public function findByFilter(AcctStreamFilterDto $filterDto);
}