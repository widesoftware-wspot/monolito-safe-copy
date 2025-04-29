<?php

namespace Wideti\DomainBundle\Service\Client\Dto;

class ClientStatusDto
{
    private $clientId;
    private $erpId;
    private $newStatus;
    private $statusReason;
    private $author;
    private $urlOrigin;
    private $httpMethod;

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param int $clientId
     * @return ClientStatusDto
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return int
     */
    public function getErpId()
    {
        return $this->erpId;
    }

    /**
     * @param int $erpId
     * @return ClientStatusDto
     */
    public function setErpId($erpId)
    {
        $this->erpId = $erpId;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }

    /**
     * @param int $newStatus
     * @return ClientStatusDto
     */
    public function setNewStatus($newStatus)
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusReason()
    {
        return $this->statusReason;
    }

    /**
     * @param string $statusReason
     * @return ClientStatusDto
     */
    public function setStatusReason($statusReason)
    {
        $this->statusReason = $statusReason;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return ClientStatusDto
     */
    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrlOrigin()
    {
        return $this->urlOrigin;
    }

    /**
     * @param string $urlOrigin
     * @return ClientStatusDto
     */
    public function setUrlOrigin($urlOrigin)
    {
        $this->urlOrigin = $urlOrigin;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @param string $httpMethod
     * @return ClientStatusDto
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function hasClientId()
    {
        return !empty($this->clientId);
    }

    public function hasErpId()
    {
        return !empty($this->erpId);
    }
}
