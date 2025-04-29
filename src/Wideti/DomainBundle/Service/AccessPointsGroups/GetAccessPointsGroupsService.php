<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;

/**
 * Class GetAccessPointsGroups
 * @package Wideti\DomainBundle\Service\AccessPointsGroups
 */
class GetAccessPointsGroupsService
{
    /**
     * @var AccessPointsGroupsRepository
     */
    private $accessPointsGroupRepository;

    /**
     * GetAccessPointsGroups constructor.
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    public function __construct(AccessPointsGroupsRepository $accessPointsGroupsRepository)
    {
        $this->accessPointsGroupRepository = $accessPointsGroupsRepository;
    }

	/**
	 * @param $groupId
	 * @param Client $client
	 * @return object|\Wideti\DomainBundle\Entity\AccessPointsGroups|null
	 */
    public function get($groupId, Client $client)
    {
        return $this->accessPointsGroupRepository->getGroupById($groupId, $client);
    }
}