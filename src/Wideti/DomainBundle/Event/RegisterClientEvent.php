<?php

namespace Wideti\DomainBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Mikrotik;

class RegisterClientEvent extends Event
{
    /**
     * @var Client
     */
    protected $client;
    protected $mikrotik;

    public function __construct(Client $client, Mikrotik $mikrotik = null)
    {
        $this->client   = $client;
        $this->mikrotik = $mikrotik;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getMikrotik()
    {
        return $this->mikrotik;
    }
}
