<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Dto;

class SmsMarketingReportDto
{
    private $lot;
    private $total;
    private $totalSent;
    private $totalDelivered;
    private $totalPending;
    private $totalError;

    /**
     * SmsMarketingReportDto constructor.
     * @param $lot
     * @param $total
     * @param $totalSent
     * @param $totalDelivered
     * @param $totalPending
     * @param $totalError
     */
    public function __construct($lot, $total, $totalSent, $totalDelivered, $totalPending, $totalError)
    {
        $this->lot = $lot;
        $this->total = $total;
        $this->totalSent = $totalSent;
        $this->totalDelivered = $totalDelivered;
        $this->totalPending = $totalPending;
        $this->totalError = $totalError;
    }

    /**
     * @return mixed
     */
    public function getLot()
    {
        return $this->lot;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return mixed
     */
    public function getTotalSent()
    {
        return $this->totalSent;
    }

    /**
     * @return mixed
     */
    public function getTotalDelivered()
    {
        return $this->totalDelivered;
    }

    /**
     * @return mixed
     */
    public function getTotalPending()
    {
        return $this->totalPending;
    }

    /**
     * @return mixed
     */
    public function getTotalError()
    {
        return $this->totalError;
    }
}
