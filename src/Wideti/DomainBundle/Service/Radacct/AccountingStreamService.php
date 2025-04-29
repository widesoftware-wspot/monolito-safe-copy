<?php

namespace Wideti\DomainBundle\Service\Radacct;

use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamDto;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

interface AccountingStreamService
{
    /**
     * @param AcctStreamFilterDto $filter
     * @return AcctStreamDto
     */
    public function get(AcctStreamFilterDto $filter);
}
