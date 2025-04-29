<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups;

use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;

/**
 * Class SetMasterAccessPointsGroups
 * @package Wideti\DomainBundle\Service\AccessPointsGroups
 */
class SetMasterAccessPointsGroups
{
    /**
     * @var AccessPointsGroupsRepository
     */
    private $accessPointsGroupsRepository;

    /**
     * SetMasterAccessPointsGroups constructor.
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    public function __construct(AccessPointsGroupsRepository $accessPointsGroupsRepository)
    {
        $this->accessPointsGroupsRepository = $accessPointsGroupsRepository;
    }

    public function persist(AccessPointsGroups $accessPointsGroups)
    {
        $this->accessPointsGroupsRepository->update($accessPointsGroups);
    }
}