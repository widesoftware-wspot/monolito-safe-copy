<?php

namespace Wideti\DomainBundle\Service\Radacct\Dto;

class AcctStreamDto implements \JsonSerializable
{
    /** @var int */
    private $totalRegistries;
    /** @var string */
    private $nextToken;
    /** @var AcctDataDto[] */
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    /**
     * @return int
     */
    public function getTotalRegistries()
    {
        return $this->totalRegistries;
    }

    /**
     * @param int $totalRegistries
     * @return AcctStreamDto
     */
    public function setTotalRegistries($totalRegistries)
    {
        $this->totalRegistries = $totalRegistries;
        return $this;
    }

    /**
     * @return string
     */
    public function getNextToken()
    {
        return $this->nextToken;
    }

    /**
     * @param string $nextToken
     * @return AcctStreamDto
     */
    public function setNextToken($nextToken)
    {
        $this->nextToken = $nextToken;
        return $this;
    }

    /**
     * @return AcctDataDto[]
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param AcctDataDto[] $data
     * @return AcctStreamDto
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param AcctDataDto $data
     * @return AcctStreamDto
     */
    public function addData(AcctDataDto $data)
    {
        $this->data[] = $data;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        return $properties;
    }
}
