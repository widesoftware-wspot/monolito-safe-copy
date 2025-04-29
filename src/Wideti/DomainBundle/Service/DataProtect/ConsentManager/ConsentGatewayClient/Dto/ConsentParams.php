<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto;


class ConsentParams
{
    private $consentId;

    private $clientID;
    /**
     * @var BaseParam
     */
    private $body;
    /**
     * @var BaseParam
     */
    private $headers;
    /**
     * @var BaseParam
     */
    private $querySearch;

    private function __construct()
    {
    }

    public static function newParams(){
        return new ConsentParams();
    }

    public function withClientId($clientId){
        $this->clientID = $clientId;
        return $this;
    }

    public function withConsentId($consentId){
        $this->consentId = $consentId;
        return $this;
    }

    public function withHeader(BaseParam $header){
        $this->headers = $header;
        return $this;
    }

    public function withBody(BaseParam $body){
        $this->body = $body;
        return $this;
    }

    public function withQuery(BaseParam $query){
        $this->querySearch = $query;
        return $this;
    }

    public function getBody()
    {
        if (is_null($this->body)){
            throw new \RuntimeException("Value body cannot be null");
        }
        return $this->body->get();
    }

    public function getClientID()
    {
        if (is_null($this->clientID)){
            throw new \RuntimeException("Value clientID cannot be null");
        }
        return $this->clientID;
    }

    public function getConsentId()
    {
        if (is_null($this->consentId)){
            throw new \RuntimeException("Value consentId cannot be null");
        }
        return $this->consentId;
    }

    public function getQuerySearch()
    {
        if (is_null($this->querySearch)){
            throw new \RuntimeException("Value querySearch cannot be null");
        }
        return $this->querySearch->get();
    }

    public function getHeaders()
    {
        if (is_null($this->headers)){
            throw new \RuntimeException("Value headers cannot be null");
        }
        return $this->headers->get();
    }
}