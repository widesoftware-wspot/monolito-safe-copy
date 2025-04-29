<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Builder;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketingReportDto;

class SmsMarketingReportBuilder
{
    private $lot;
    private $total;
    private $totalSent;
    private $totalDelivered;
    private $totalPending;
    private $totalError;

    public static function getBuilder()
    {
        return new SmsMarketingReportBuilder();
    }

    /**
     * @param $lot
     * @return $this
     */
    public function withLot($lot)
    {
        $this->lot = $lot;
        return $this;
    }

    /**
     * @param $total
     * @return $this
     */
    public function withTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @param $totalSent
     * @return $this
     */
    public function withTotalSent($totalSent)
    {
        $this->totalSent = $totalSent;
        return $this;
    }

    /**
     * @param $totalDelivered
     * @return $this
     */
    public function withTotalDelivered($totalDelivered)
    {
        $this->totalDelivered = $totalDelivered;
        return $this;
    }

    /**
     * @param $totalPending
     * @return $this
     */
    public function withTotalPending($totalPending)
    {
        $this->totalPending = $totalPending;
        return $this;
    }

    /**
     * @param $totalError
     * @return $this
     */
    public function withTotalError($totalError)
    {
        $this->totalError = $totalError;
        return $this;
    }

    /**
     * @return SmsMarketingReportDto
     */
    public function build()
    {
        return new SmsMarketingReportDto(
            $this->lot,
            $this->total,
            $this->totalSent,
            $this->totalDelivered,
            $this->totalPending,
            $this->totalError
        );
    }
}
