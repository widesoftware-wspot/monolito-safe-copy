<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Dto;

class TotalGuestsFilter implements \JsonSerializable
{
    private $domain;
    private $mobileField;
    private $group;
    private $ddd;
    private $dateFrom;
    private $dateTo;

    /**
     * TotalGuestsFilter constructor.
     * @param $domain
     * @param $mobileField
     * @param $group
     * @param $ddd
     * @param $dateFrom
     * @param $dateTo
     */
    public function __construct($domain, $mobileField, $group, $ddd, $dateFrom, $dateTo)
    {
        $this->domain = $domain;
        $this->mobileField = $mobileField;
        $this->group = $group ?: "all";
        $this->ddd = $ddd ?: "all";
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return mixed
     */
    public function getMobileField()
    {
        return $this->mobileField;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return mixed
     */
    public function getDdd()
    {
        return $this->ddd;
    }

    /**
     * @return mixed
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @return mixed
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
