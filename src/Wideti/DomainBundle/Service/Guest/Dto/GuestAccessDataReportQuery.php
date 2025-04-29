<?php

namespace Wideti\DomainBundle\Service\Guest\Dto;

use Doctrine\ODM\MongoDB\Query\Query;
use Wideti\DomainBundle\Service\RadacctReport\Dto\GuestAccessReport;

class GuestAccessDataReportQuery
{
    /**
     * @var Query
     */
    private $mongoQuery;
    /**
     * @var GuestAccessReport[]
     */
    private $guestData;

    /**
     * GuestAccessDataReportQuery constructor.
     * @param Query $mongoQuery
     * @param GuestAccessReport[] $guestData
     */
    public function __construct(Query $mongoQuery, array $guestData)
    {
        $this->mongoQuery = $mongoQuery;
        $this->guestData = $guestData;
    }

    /**
     * @return Query
     */
    public function getMongoQuery()
    {
        return $this->mongoQuery;
    }

    /**
     * @return GuestAccessReport[]
     */
    public function getGuestData()
    {
        return $this->guestData;
    }
}
