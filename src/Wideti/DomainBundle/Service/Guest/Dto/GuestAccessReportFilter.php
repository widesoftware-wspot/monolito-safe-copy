<?php

namespace Wideti\DomainBundle\Service\Guest\Dto;

class GuestAccessReportFilter
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

    /**
     * GuestAccessReportFilter constructor.
     * @param string $fieldToFilter
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string $recurrence
     */
    public function __construct($fieldToFilter, \DateTime $dateFrom, \DateTime $dateTo, $recurrence)
    {
        $this->fieldToFilter    = $fieldToFilter;
        $this->dateFrom         = $dateFrom;
        $this->dateTo           = $dateTo;
        $this->recurrence       = $recurrence;
    }

    /**
     * @return string
     */
    public function getFieldToFilter()
    {
        return $this->fieldToFilter;
    }

    /**
     * @return \DateTime
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @return \DateTime
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return string
     */
    public function getRecurrence()
    {
        return $this->recurrence;
    }

    public function getAsArray()
    {
        return [
            'recurrent' => $this->recurrence,
            'filter'    => $this->fieldToFilter,
            'dateTo'    => $this->dateTo,
            'dateFrom'  => $this->dateFrom,
        ];
    }
}