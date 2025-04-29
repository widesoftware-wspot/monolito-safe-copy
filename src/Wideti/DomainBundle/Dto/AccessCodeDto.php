<?php

namespace Wideti\DomainBundle\Dto;

class AccessCodeDto
{
    const FREE_ACCESS = "freeAccess";

    public $hasAccessCode      = false;
    public $accessCodeIds      = [];
    public $accessCodeParams   = [];
    public $hasFreeAccess      = false;
    public $freeAccessParams   = [];

    /**
     * @return boolean
     */
    public function isHasAccessCode()
    {
        return $this->hasAccessCode;
    }

    /**
     * @param boolean $hasAccessCode
     */
    public function setHasAccessCode($hasAccessCode)
    {
        $this->hasAccessCode = $hasAccessCode;
    }

    /**
     * @return null
     */
    public function getAccessCodeIds()
    {
        return $this->accessCodeIds;
    }

    /**
     * @param null $accessCodeIds
     */
    public function setAccessCodeIds($accessCodeIds)
    {
        $this->accessCodeIds = $accessCodeIds;
    }

    /**
     * @return array
     */
    public function getAccessCodeParams()
    {
        return $this->accessCodeParams;
    }

    /**
     * @param array $accessCodeParams
     */
    public function setAccessCodeParams($accessCodeParams)
    {
        $this->accessCodeParams = $accessCodeParams;
    }

    /**
     * @return boolean
     */
    public function isHasFreeAccess()
    {
        return $this->hasFreeAccess;
    }

    /**
     * @param boolean $hasFreeAccess
     */
    public function setHasFreeAccess($hasFreeAccess)
    {
        $this->hasFreeAccess = $hasFreeAccess;
    }

    /**
     * @return array
     */
    public function getFreeAccessParams()
    {
        return $this->freeAccessParams;
    }

    /**
     * @param array $freeAccessParams
     */
    public function setFreeAccessParams($freeAccessParams)
    {
        $this->freeAccessParams = $freeAccessParams;
    }
}
