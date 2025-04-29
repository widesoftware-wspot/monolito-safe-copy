<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Builder;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;

class SmsMarketingBuilder
{
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

    public static function getBuilder()
    {
        return new SmsMarketingBuilder();
    }

    /**
     * @param $id
     * @return $this
     */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $clientId
     * @return $this
     */
    public function withClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param $adminUserId
     * @return $this
     */
    public function withAdminUserId($adminUserId)
    {
        $this->adminUserId = $adminUserId;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $lotNumber
     * @return $this
     */
    public function withLotNumber($lotNumber)
    {
        $this->lotNumber = $lotNumber;
        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function withQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param $totalSms
     * @return $this
     */
    public function withTotalSms($totalSms)
    {
        $this->totalSms = $totalSms;
        return $this;
    }

    /**
     * @param $urlShortnedType
     * @return $this
     */
    public function withUrlShortnedType($urlShortnedType)
    {
        $this->urlShortnedType = $urlShortnedType;
        return $this;
    }

    /**
     * @param $urlShortned
     * @return $this
     */
    public function withUrlShortned($urlShortned)
    {
        $this->urlShortned = $urlShortned;
        return $this;
    }

    /**
     * @param $urlShortnedHash
     * @return $this
     */
    public function withUrlShortnedHash($urlShortnedHash)
    {
        $this->urlShortnedHash = $urlShortnedHash;
        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    public function withMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param $createdAt
     * @return $this
     */
    public function withCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @param $sentAt
     * @return $this
     */
    public function withSentAt($sentAt)
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    /**
     * @param $updatedAt
     * @return $this
     */
    public function withUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function build()
    {
        $dto = new SmsMarketing();
        $dto->setId($this->id);
        $dto->setClientId($this->clientId);
        $dto->setAdminUserId($this->adminUserId);
        $dto->setStatus($this->status);
        $dto->setLotNumber($this->lotNumber);
        $dto->setQuery($this->query);
        $dto->setTotalSms($this->totalSms);
        $dto->setUrlShortnedType($this->urlShortnedType);
        $dto->setUrlShortned($this->urlShortned);
        $dto->setUrlShortnedHash($this->urlShortnedHash);
        $dto->setMessage($this->message);
        $dto->setCreatedAt($this->createdAt);
        $dto->setSentAt($this->sentAt);
        $dto->setUpdatedAt($this->updatedAt);
        return $dto;
    }
}
