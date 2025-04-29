<?php

namespace Wideti\DomainBundle\Service\SearchAccessPointsAndGroups;


use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroup;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroupEntitys;

interface SearchAccessPointsAndGroups
{
    /**
     * @param string $searchName
     * @param Client $client
     * @return AccessPointAndGroup[]
     */
    public function findAccessPointAndGroups($searchName, Client $client);

    /**
     * @param $searchName
     * @param Client $client
     * @param $apIds
     * @param $groupIds
     * @return mixed
     */
    public function findAccessPointAndGroupsNotWithIds($searchName, Client $client, $apIds, $groupIds);

    /**
     * @param array $apsAndGroups
     * @return AccessPointAndGroupEntitys
     */
    public function convertToEntity(array $apsAndGroups);

    /**
     * @param $campaignId
     * @param Client $client
     * @return AccessPointAndGroup[]
     */
    public function findByCampaignId($campaignId, Client $client);

    /**
     * @param $guestGroupId
     * @return mixed
     */
    public function findByGuestGroupId($guestGroupId);
}
