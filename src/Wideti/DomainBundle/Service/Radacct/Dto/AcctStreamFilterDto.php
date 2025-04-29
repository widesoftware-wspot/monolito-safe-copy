<?php

namespace Wideti\DomainBundle\Service\Radacct\Dto;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\DateInvalidException;
use Wideti\DomainBundle\Exception\InvalidGuestIdException;
use Wideti\DomainBundle\Helpers\StringToDateTimeHelper;

class AcctStreamFilterDto
{
    /** @var \DateTime */
    private $from;

    /** @var \DateTime */
    private $to;

    /** @var string */
    private $order;

    /** @var string */
    private $nextToken;

    /** @var integer */
    private $guest;

    /** @var string */
    private $identifier;

    /** @var Client */
    private $client;

    /**
     * @param Request $request
     * @param Client $client
     * @return AcctStreamFilterDto
     * @throws DateInvalidException
     * @throws InvalidGuestIdException
     */
    public static function createFromRequest(Request $request, Client $client)
    {
        $from = $request->get("from")
            ? StringToDateTimeHelper::create($request->get("from"))
            : null;
        $to = $request->get("to")
            ? StringToDateTimeHelper::create($request->get("to"))
            : null;

        $filter = new AcctStreamFilterDto();
        $filter
            ->setClient($client)
            ->setIdentifier($request->get("identifier"))
            ->setGuest(self::extractGuestId($request))
            ->setNextToken($request->get("nextToken"))
            ->setOrder($request->get("order"))
            ->setFrom($from)
            ->setTo($to);
        ;

        return $filter;
    }

    /**
     * @param Request $request
     * @return int
     * @throws InvalidGuestIdException
     */
    private static function extractGuestId(Request $request)
    {
        $guestId = $request->get("guest");
        if (!$guestId) return null;

        if (is_int($guestId)) return $guestId;

        if (!ctype_digit($guestId))
            throw new InvalidGuestIdException("Campo guest inválido, deve ser um número inteiro");

        return (int) $guestId;
    }

    /**
     * @return \DateTime
     */
    public function getFrom()
    {
        return $this->from
            ? clone $this->from
            : null;
    }

    /**
     * @param \DateTime $from
     * @return AcctStreamFilterDto
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTo()
    {
        return $this->to
            ? clone $this->to
            : null;
    }

    /**
     * @param \DateTime $to
     * @return AcctStreamFilterDto
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     * @return AcctStreamFilterDto
     */
    public function setOrder($order)
    {
        $this->order = $order;
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
     * @return AcctStreamFilterDto
     */
    public function setNextToken($nextToken)
    {
        $this->nextToken = $nextToken;
        return $this;
    }

    /**
     * @return int
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param int $guest
     * @return AcctStreamFilterDto
     */
    public function setGuest($guest)
    {
        $this->guest = $guest;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return AcctStreamFilterDto
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client
            ? clone $this->client
            : null;
    }

    /**
     * @param Client $client
     * @return AcctStreamFilterDto
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }
}
