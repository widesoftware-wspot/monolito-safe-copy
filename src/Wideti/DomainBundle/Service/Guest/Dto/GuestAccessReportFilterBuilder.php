<?php

namespace Wideti\DomainBundle\Service\Guest\Dto;

class GuestAccessReportFilterBuilder
{
    /**
     * @var string
     */
    private $fieldToFilter;
    /**
     * @var \DateTime
     */
    private $dateFrom;
    /**
     * @var \DateTime
     */
    private $dateTo;
    /**
     * @var string
     */
    private $recurrence;

    public static function getBuilder()
    {
        return new GuestAccessReportFilterBuilder();
    }

    /**
     * @param string $fieldToFilter
     * @return $this
     */
    public function withFieldToFilter($fieldToFilter)
    {
        $this->fieldToFilter = $fieldToFilter;
        return $this;
    }

    /**
     * @param \DateTime $dateFrom
     * @return $this
     */
    public function withDateFrom(\DateTime $dateFrom)
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    /**
     * @param \DateTime $dateTo
     * @return $this
     */
    public function withDateTo(\DateTime $dateTo)
    {
        $this->dateTo = $dateTo;
        return $this;
    }

    /**
     * @param string $recurrence
     * @return $this
     */
    public function withRecurrence($recurrence = null)
    {
        $this->recurrence = $recurrence;
        return $this;
    }

    public function build()
    {
        return new GuestAccessReportFilter(
            $this->fieldToFilter,
            $this->dateFrom,
            $this->dateTo,
            $this->recurrence
        );
    }
}
