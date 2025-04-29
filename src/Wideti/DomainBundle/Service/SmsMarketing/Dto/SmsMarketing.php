<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Dto;

class SmsMarketing implements \JsonSerializable
{
    const STATUS_DRAFT      = "DRAFT";
    const STATUS_SENT       = "SENT";
    const STATUS_PROCESSING = "PROCESSING";
    const STATUS_REMOVED    = "REMOVED";

    private $id;
    private $clientId;
    private $adminUserId;
    private $status;
    private $lotNumber;
    private $query;
    private $totalSms;
    private $urlShortnedType;
    private $urlShortned;
    private $urlShortnedHash;
    private $message;
    private $createdAt;
    private $sentAt;
    private $updatedAt;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getAdminUserId()
    {
        return $this->adminUserId;
    }

    /**
     * @param mixed $adminUserId
     */
    public function setAdminUserId($adminUserId)
    {
        $this->adminUserId = $adminUserId;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

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
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getTotalSms()
    {
        return $this->totalSms;
    }

    /**
     * @param mixed $totalSms
     */
    public function setTotalSms($totalSms)
    {
        $this->totalSms = $totalSms;
    }

    /**
     * @return mixed
     */
    public function getUrlShortnedType()
    {
        return $this->urlShortnedType;
    }

    /**
     * @param mixed $urlShortnedType
     */
    public function setUrlShortnedType($urlShortnedType)
    {
        $this->urlShortnedType = $urlShortnedType;
    }

    /**
     * @return mixed
     */
    public function getUrlShortned()
    {
        return $this->urlShortned;
    }

    /**
     * @param mixed $urlShortned
     */
    public function setUrlShortned($urlShortned)
    {
        $this->urlShortned = $urlShortned;
    }

    /**
     * @return mixed
     */
    public function getUrlShortnedHash()
    {
        return $this->urlShortnedHash;
    }

    /**
     * @param mixed $urlShortnedHash
     */
    public function setUrlShortnedHash($urlShortnedHash)
    {
        $this->urlShortnedHash = $urlShortnedHash;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * @param mixed $sentAt
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
