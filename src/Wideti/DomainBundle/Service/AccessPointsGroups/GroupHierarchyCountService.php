<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups;

use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;

/**
 * Class GroupHierarchyCountService
 * @package Wideti\DomainBundle\Service\AccessPointsGroups
 */
class GroupHierarchyCountService
{
    /**
     * @var AccessPointsGroupsRepository
     */
    private $accessPointsGroupsRepository;

    /**
     * GroupHierarchyCountService constructor.
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    public function __construct(AccessPointsGroupsRepository $accessPointsGroupsRepository)
    {
        $this->accessPointsGroupsRepository = $accessPointsGroupsRepository;
    }

    /**
     * @param $groupId
     * @return mixed
     */
    public function count($groupId)
    {
        return $this->accessPointsGroupsRepository->getGroupHierarchyCount($groupId);
    }
}