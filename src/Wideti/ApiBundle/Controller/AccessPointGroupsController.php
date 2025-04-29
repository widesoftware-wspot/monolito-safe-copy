<?php

namespace Wideti\ApiBundle\Controller;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\AccessPointsGroups\Dto\AccessPointGroupsApiDto;
use Wideti\DomainBundle\Service\AccessPointsGroups\Dto\AccessPointGroupsFilterDto;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestService;

class AccessPointGroupsController implements ApiResource
{
    const RESOURCE_NAME = 'access_point_groups';

    /**
     * @var SelectClientByRequestService
     */
    private $selectClientByRequest;

    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AccessPointGroupsController constructor.
     * @param SelectClientByRequestService $selectClientByRequest
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param Logger $logger
     */
    public function __construct
    (
        SelectClientByRequestService $selectClientByRequest,
        AccessPointsGroupsService $accessPointsGroupsService,
        Logger $logger
    ) {
        $this->selectClientByRequest = $selectClientByRequest;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        try {
            $client = $this->selectClientByRequest->get($request);
            $filter = AccessPointGroupsFilterDto::createFromRequest($request, $client);
            $accessPointsGroups = $this->accessPointsGroupsService->findByFilter($filter);
            $apiAccessPointGroups = AccessPointGroupsApiDto::createFromAccessPointGroupArray($accessPointsGroups);

            $httpStatus = empty($apiAccessPointGroups)
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_OK;

            return new JsonResponse($apiAccessPointGroups, $httpStatus);
        } catch (\Exception $ex) {
            $this->logger->addCritical('[API ERROR] Access Point Group list error.', [
                'error' => $ex->getTraceAsString()
            ]);

            return new JsonResponse([
                "error" => 'Ocorreu um erro, nossa equipe já foi notificada.'
            ], 500);
        }
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
