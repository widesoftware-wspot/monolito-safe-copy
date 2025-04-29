<?php

namespace Wideti\DomainBundle\Service\Watchdog\Dto;

use Wideti\DomainBundle\Entity\Client;

class VerifyAccessPoint
{
    private $mongoWrongAps;
    private $elasticWrongAps;
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->mongoWrongAps = [];
        $this->elasticWrongAps = [];
        $this->client = $client;
    }

    /**
     * @param string $mac
     */
    public function addMongoWrongMac($mac)
    {
        $this->mongoWrongAps[] = $mac;
    }

    /**
     * @param string $mac
     */
    public function addElasticWrongMac($mac)
    {
        $this->elasticWrongAps[] = $mac;
    }

    /**
     * @return bool
     */
    public function hasMongoWrongAps()
    {
        return count($this->mongoWrongAps) > 0;
    }

    /**
     * @return bool
     */
    public function hasElasticWrongAps()
    {
        return count($this->elasticWrongAps) > 0;
    }

    /**
     * @return string[]
     */
    public function getMongoWrongAps()
    {
        return $this->mongoWrongAps;
    }

    /**
     * @return string[]
     */
    public function getElasticWrongAps()
    {
        return $this->elasticWrongAps;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
