<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups\Dto;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;

class AccessPointGroupsFilterDto
{
    const DEFAULT_PAGE_NUMBER = 0;
    const DEFAULT_PAGE_LIMIT = 10;
    const MAX_PAGE_LIMIT = 50;

    /**
     * @var string
     */
    private $groupName;

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
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @param $groupName
     * @return $this
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
        return $this;
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
     * @return AccessPointGroupsFilterDto
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
     * @return AccessPointGroupsFilterDto
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
     * @return AccessPointGroupsFilterDto
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
     * @return bool
     */
    public function hasGroupName()
    {
        return !empty($this->groupName);
    }

    /**
     * @param Request $request
     * @param Client $client
     * @return AccessPointGroupsFilterDto
     */
    public static function createFromRequest(Request $request, Client $client)
    {
        if (!$client || !$request) {
            throw new \InvalidArgumentException('Need Request and  Client to create a filter.');
        }

        $requestPageLimit = $request->get('limit', self::DEFAULT_PAGE_LIMIT);
        $limit =  $requestPageLimit <= self::MAX_PAGE_LIMIT
            ? $requestPageLimit
            : self::MAX_PAGE_LIMIT;

        $filter = new AccessPointGroupsFilterDto();
        $filter
            ->setClient($client)
            ->setGroupName($request->get('name'))
            ->setPage($request->get('page', self::DEFAULT_PAGE_NUMBER))
            ->setLimit($limit);

        return $filter;
    }
}
