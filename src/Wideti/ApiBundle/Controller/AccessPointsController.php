<?php

namespace Wideti\ApiBundle\Controller;

use Respect\Validation\Exceptions\NestedValidationException;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Wideti\DomainBundle\Exception\AccessPointExistsException;
use Wideti\DomainBundle\Exception\EmptyFieldsToUpdateException;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointApiValidator;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\AccessPoints\BuildAccessPointFromApiCreateDtoService;
use Wideti\DomainBundle\Service\AccessPoints\Dto\AccessPointFilterDto;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\AccessPointDto;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestService;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class AccessPointsController implements ApiResource
{
    use EntityManagerAware;

    const RESOURCE_NAME = 'access_points';

    /**
     * @var SelectClientByRequestService
     */
    private $selectClientByRequest;

    /**
     * @var AccessPointsService
     */
    private $accessPointsService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var VendorService
     */
    private $vendorService;
    /**
     * @var AccessPointApiValidator
     */
    private $accessPointApiValidator;
    /**
     * @var BuildAccessPointFromApiCreateDtoService
     */
    private $buildEntityService;

    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;

    public function __construct(
        SelectClientByRequestService $selectClientByRequest,
        AccessPointsService $accessPointsService,
        Logger $logger,
        VendorService $vendorService,
        AccessPointApiValidator $accessPointApiValidator,
        AccessPointsGroupsService $accessPointsGroupsService,
        BuildAccessPointFromApiCreateDtoService $buildEntityService
    ) {
        $this->selectClientByRequest = $selectClientByRequest;
        $this->accessPointsService = $accessPointsService;
        $this->logger = $logger;
        $this->vendorService = $vendorService;
        $this->accessPointApiValidator = $accessPointApiValidator;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->buildEntityService = $buildEntityService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        try {
            $client = $this->selectClientByRequest->get($request);
            $filter = AccessPointFilterDto::createFromRequest($request, $client);
            $accessPoints = $this->accessPointsService->findByFilter($filter);
            $apiAccessPoints = AccessPointDto::createFromAccessPointArray($accessPoints);

            $httpStatus = empty($apiAccessPoints) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;

            return new JsonResponse($apiAccessPoints, $httpStatus);
        } catch (\Exception $ex) {
            $this->logger->addCritical('[API ERROR] Access Point list error.', [
                'error' => $ex->getTraceAsString()
            ]);

            return new JsonResponse([
                "error" => 'Ocorreu um erro, nossa equipe já foi notificada.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function detailAction(Request $request)
    {
        $accessPointId = (int) $request->get('id', null);
        if (!$accessPointId) return new JsonResponse(['message' => 'Id inválido'], 400);

        try {
            $client = $this->selectClientByRequest->get($request);
            $accessPoint = $this->accessPointsService->findByIdAndClient($accessPointId, $client);
            $apiDto = AccessPointDto::createFromAccessPointEntity($accessPoint);
            if (!$apiDto) return new JsonResponse(null, 404);
            return new JsonResponse($apiDto, 200);
        } catch (\Exception $ex) {
            $this->logger->addCritical('[API ERROR] Access Point detail error.', [
                'error' => $ex->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => 'Ocorreu um erro, nossa equipe já foi notificada.'
            ], 500);
        }
    }
    
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function listVendorsAction(Request $request)
    {
        $vendors = $this->vendorService->getVendors();

        for ($i = 0; $i < count($vendors); $i++) {
            $vendors[$i][$vendors[$i]->getVendor()] = strtolower($vendors[$i][$vendors[$i]->getVendor()]);
        }

        return new JsonResponse($vendors);
    }

    public function createAction(Request $request)
    {
        $client = $this->selectClientByRequest->get($request);
        try {
            $accessPointDto = CreateAccessPointDto
                ::createFromAssocArray(json_decode($request->getContent(), true), $client);

            $errors = $this->accessPointApiValidator->validate($accessPointDto);

            if ($errors) {
                return new JsonResponse($errors, 400);
            }

            $accessPointEntity = $this->buildEntityService->getEntity($accessPointDto);
            $apCreated = $this->accessPointsService->createOne($accessPointEntity);
            $createdDto = AccessPointDto::createFromAccessPointEntity($apCreated);
            return new JsonResponse($createdDto, 201);

        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        } catch (AccessPointExistsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (ErrorOnCreateAccessPointException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage(), $e->getTrace());
            return new JsonResponse(
                ['error' => 'Ocorreu um erro no servidor, nossa equipe já foi notificada.'], 500
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        try {
            $accessPointId = (int)$request->get("id");
            $client = $this->selectClientByRequest->get($request);

            $jsonRequest = json_decode($request->getContent(), true);
            $jsonRequest["id"] = $accessPointId;

            $accessPointDto = CreateAccessPointDto::createFromAssocArray($jsonRequest, $client);

            $errors = $this->accessPointApiValidator->validate($accessPointDto);
            if ($errors) {
                return new JsonResponse($errors, 400);
            }

            $station = $this->buildEntityService->getEntity($accessPointDto);

            $this->accessPointsService->update($station);

            return new JsonResponse([], 204);

        } catch (EmptyFieldsToUpdateException $exception) {
            return new JsonResponse(['error' => "Não existem campos na requisição para serem atualizados."], 400);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => 'Ocorreu um erro interno, nossa equipe já foi notificada. ' . $exception->getMessage()], 500);
        }
    }

    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
