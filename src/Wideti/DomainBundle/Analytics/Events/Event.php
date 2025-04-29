<?php


namespace Wideti\DomainBundle\Analytics\Events;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

class Event
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var array
     */
    private $extraData;
    /**
     * @var string
     */
    private $eventIdentifier;
    /**
     * @var Nas
     */
    private $nas;

    /**
     * @var Session session
     */
    private $session;
    /**
     * @var string
     */
    private $eventType;
    /**
     * @var Guest
     */
    private $guest;



    /**
     * @param Client $client
     * @return $this
     */
    public function withClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param string $eventIdentifier
     * @return $this
     */
    public function withEventIdentifier($eventIdentifier)
    {
        $this->eventIdentifier = $eventIdentifier;
        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function withRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param array $extraData
     * @return $this
     */
    public function withExtraData($extraData)
    {
        $this->extraData = $extraData;
        return $this;
    }

    /**
     * @param Nas $nas
     * @return $this
     */
    public function withNas($nas)
    {
        $this->nas = $nas;
        return $this;
    }

    /**
     * @param Session $session
     * @return $this
     */
    public function withSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @param string $eventType
     * @return $this
     */
    public function withEventType($eventType)
    {
        $this->eventType = $eventType;
        return $this;
    }

    /**
     * @param Guest $guest
     * @return $this
     */
    public function withGuest($guest)
    {
        $this->guest = $guest;
        return $this;
    }

    /**
     * @return Event
     */
    public function build()
    {
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @return EventIdentifier
     */
    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    /**
     * @return Nas
     */
    public function getNas()
    {
        return $this->nas;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return EventType
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @return Guest
     */
    public function getGuest()
    {
        return $this->guest;
    }


}
