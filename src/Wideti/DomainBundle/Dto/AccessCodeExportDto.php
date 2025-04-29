<?php
namespace Wideti\DomainBundle\Dto;

class AccessCodeExportDto
{
    private $lotNumber;
    private $accessCode;
    private $connectionTime;
    private $used;
    private $usedDate;
    private $usedBy;

    /**
     * @return mixed
     */
    public function getLotNumber()
    {
        return $this->lotNumber;
    }

    /**
     * @param mixed $lotNumber
     */
    public function setLotNumber($lotNumber)
    {
        $this->lotNumber = $lotNumber;
    }

    /**
     * @return mixed
     */
    public function getAccessCode()
    {
        return $this->accessCode;
    }

    /**
     * @param mixed $accessCode
     */
    public function setAccessCode($accessCode)
    {
        $this->accessCode = $accessCode;
    }

    /**
     * @return mixed
     */
    public function getConnectionTime()
    {
        return $this->connectionTime;
    }

    /**
     * @param mixed $connectionTime
     */
    public function setConnectionTime($connectionTime)
    {
        $this->connectionTime = $connectionTime;
    }

    /**
     * @return mixed
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * @param mixed $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }

    /**
     * @return mixed
     */
    public function getUsedDate()
    {
        return $this->usedDate;
    }

    /**
     * @param mixed $usedDate
     */
    public function setUsedDate($usedDate)
    {
        $this->usedDate = $usedDate;
    }

    /**
     * @return mixed
     */
    public function getUsedBy()
    {
        return $this->usedBy;
    }

    /**
     * @param mixed $usedBy
     */
    public function setUsedBy($usedBy)
    {
        $this->usedBy = $usedBy;
    }

    public function getValuesAsArray()
    {
        return array_values(get_object_vars($this));
    }
}
