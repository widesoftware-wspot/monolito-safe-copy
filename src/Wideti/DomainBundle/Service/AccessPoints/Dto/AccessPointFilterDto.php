<?php

namespace Wideti\DomainBundle\Service\AccessPoints\Dto;

use Proxies\__CG__\Wideti\DomainBundle\Entity\AccessPoints;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;

class AccessPointFilterDto
{
    const DEFAULT_PAGE_NUMBER = 0;
    const DEFAULT_PAGE_LIMIT = 50;

    /**
     * @var string
     */
    private $friendlyName;
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var int
     */
    private $status;
    /**
     * @var int
     */
    private $page;
    /**
     * @var int
     */
    private $limit;

    /**
     * @var Client
     */
    private $client;

    /**
     * @return string
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    /**
     * @param string $friendlyName
     * @return AccessPointFilterDto
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasFriendlyName()
    {
        return !empty($this->friendlyName);
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
     * @return AccessPointFilterDto
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasIdentifier()
    {
        return !empty($this->identifier);
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return AccessPointFilterDto
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasStatus()
    {
        return $this->status !== null;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return AccessPointFilterDto
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return AccessPointFilterDto
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return AccessPointFilterDto
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasClient()
    {
        return $this->client !== null;
    }

    /**
     * @param Request $request
     * @param Client $client
     * @return AccessPointFilterDto
     */
    public static function createFromRequest(Request $request, Client $client)
    {
        if (!$client || !$request) {
            throw new \InvalidArgumentException('Need Request and Client to create a filter.');
        }

        $filter = new AccessPointFilterDto();
        $filter
            ->setIdentifier($request->get('identifier', ''))
            ->setFriendlyName($request->get('friendlyName', ''))
            ->setStatus($request->get('status', null))
            ->setPage($request->get('page', self::DEFAULT_PAGE_NUMBER))
            ->setLimit($request->get('limit', self::DEFAULT_PAGE_LIMIT))
            ->setClient($client);

        return $filter;
    }
}
