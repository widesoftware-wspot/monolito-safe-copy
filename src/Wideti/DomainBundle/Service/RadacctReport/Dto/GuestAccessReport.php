<?php

namespace Wideti\DomainBundle\Service\RadacctReport\Dto;

class GuestAccessReport
{
    private $userNameId;
    private $guestName;
    private $loginFieldValue;
    private $registerDate;
    private $lastAccessDate;
    private $totalOfVisits;
    private $downloadTotal;
    private $uploadTotal;
    private $averageTime;

    /**
     * @return int
     */
    public function getUserNameId()
    {
        return $this->userNameId;
    }

    /**
     * @param int $userNameId
     */
    public function setUserNameId($userNameId)
    {
        $this->userNameId = $userNameId;
    }

    /**
     * @return string
     */
    public function getGuestName()
    {
        return $this->guestName;
    }

    /**
     * @param string $guestName
     */
    public function setGuestName($guestName)
    {
        $this->guestName = $guestName;
    }

    /**
     * @return string
     */
    public function getLoginFieldValue()
    {
        return $this->loginFieldValue;
    }

    /**
     * @param string $loginFieldValue
     */
    public function setLoginFieldValue($loginFieldValue)
    {
        $this->loginFieldValue = $loginFieldValue;
    }

    /**
     * @return string
     */
    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    /**
     * @param string $registerDate
     */
    public function setRegisterDate($registerDate)
    {
        $this->registerDate = $registerDate;
    }

    /**
     * @return string
     */
    public function getLastAccessDate()
    {
        return $this->lastAccessDate;
    }

    /**
     * @param string $lastAccessDate
     */
    public function setLastAccessDate($lastAccessDate)
    {
        $this->lastAccessDate = $lastAccessDate;
    }

    /**
     * @return int
     */
    public function getTotalOfVisits()
    {
        return $this->totalOfVisits;
    }

    /**
     * @param int $totalOfVisits
     */
    public function setTotalOfVisits($totalOfVisits)
    {
        $this->totalOfVisits = $totalOfVisits;
    }

    /**
     * @return int
     */
    public function getDownloadTotal()
    {
        return $this->downloadTotal;
    }

    /**
     * @param int $downloadTotal
     */
    public function setDownloadTotal($downloadTotal)
    {
        $this->downloadTotal = $downloadTotal;
    }

    /**
     * @return int
     */
    public function getUploadTotal()
    {
        return $this->uploadTotal;
    }

    /**
     * @param int $uploadTotal
     */
    public function setUploadTotal($uploadTotal)
    {
        $this->uploadTotal = $uploadTotal;
    }

    /**
     * @return double
     */
    public function getAverageTime()
    {
        return $this->averageTime;
    }

    /**
     * @param double $averageTime
     */
    public function setAverageTime($averageTime)
    {
        $this->averageTime = $averageTime;
    }
}
