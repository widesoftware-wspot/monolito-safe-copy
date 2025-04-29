<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\SearchAccessPointsAndGroups;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AccessPointsAndGroupsController
{

    use SessionAware;

    /**
     * @var SearchAccessPointsAndGroups
     */
    private $accessPointsAndGroups;

    public function __construct(SearchAccessPointsAndGroups $accessPointsAndGroups)
    {
        $this->accessPointsAndGroups = $accessPointsAndGroups;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAccessPointOrGroupToSelectBox(Request $request)
    {
        $search = $request->get('name');
        $apIds = $request->get('apIds');
        $groupIds = $request->get('groupIds');

        if (!$search) {
            return new JsonResponse([]);
        }

        $client = $this->getLoggedClient();
        $response = $this->accessPointsAndGroups->findAccessPointAndGroupsNotWithIds($search, $client, $apIds, $groupIds);
        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getByApsAndGroupsByCampaignId(Request $request)
    {
        $campaignId = $request->get('id');
        if (!$campaignId) {
            return new JsonResponse([]);
        }

        $client = $this->getLoggedClient();
        $result = $this->accessPointsAndGroups->findByCampaignId((int) $campaignId, $client);

        return new JsonResponse($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getByApsAndGroupsByGuestGroupId(Request $request)
    {
        $guestGroupId = $request->get('id');
        if (!$guestGroupId) {
            return new JsonResponse([]);
        }
        $result = $this->accessPointsAndGroups->findByGuestGroupId($guestGroupId);
        return new JsonResponse($result);
    }
}
