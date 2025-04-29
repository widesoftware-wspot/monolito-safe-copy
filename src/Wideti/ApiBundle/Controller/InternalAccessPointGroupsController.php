<?php

namespace Wideti\ApiBundle\Controller;

use Respect\Validation\Exceptions\JsonException;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointByInternalScriptDto;
use Wideti\DomainBundle\Service\AccessPointsGroups\Dto\AccessPointGroupsApiDto;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestService;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;


class InternalAccessPointGroupsController implements ApiResource
{
    const RESOURCE_NAME = 'internal_access_point_groups';

    /**
     * @var SelectClientByRequestService
     */
    private $selectClientByRequest;

    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @var ClientService
     */
    private $clientService;

    /**
     * AccessPointGroupsController constructor.
     * @param SelectClientByRequestService $selectClientByRequest
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param Logger $logger
     * @param ClientService $clientService
     */
    public function __construct
    (
        SelectClientByRequestService $selectClientByRequest,
        AccessPointsGroupsService $accessPointsGroupsService,
        Logger $logger,
        ClientService $clientService
    ) {
        $this->selectClientByRequest = $selectClientByRequest;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->logger = $logger;
        $this->clientService = $clientService;
    }


    public function loadAccessPointsGroups(Request $request)
    {
        try {
            $clientId = $request->get("id");

            if (is_null($clientId)) {
                throw new \InvalidArgumentException("O corpo da requisição está vazio.");
            }

            $client = $this->clientService->getClientById($clientId);

            if (!$client) {
                return new JsonResponse(null, 404);
            }

            $apGroups = $this->accessPointsGroupsService->getGroupByClient($client);

            $apiAccessPointGroups = AccessPointGroupsApiDto::createFromAccessPointGroupArray($apGroups);

            if (!$apGroups) {
                throw new JsonException("Fail to serialize aps message error");
            }

            return new JsonResponse($apiAccessPointGroups, 200);

        }catch(JsonException $e){
            $this->logger->addCritical($e->getMessage(), $e->getTrace());
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }catch (\InvalidArgumentException $e){
            $this->logger->addCritical($e->getMessage(), $e->getTrace());
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage(), $e->getTrace());
            return new JsonResponse(
                ['error'=> 'Ocorreu um erro no servidor, nossa equipe já foi notificada.'],
                500
            );
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
