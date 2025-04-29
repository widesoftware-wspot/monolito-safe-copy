<?php

namespace Wideti\ApiBundle\Controller;

use Elasticsearch\Common\Exceptions\Serializer\JsonErrorException;
use Respect\Validation\Exceptions\JsonException;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Exception\AccessPointExistsException;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointApiValidator;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\AccessPoints\BuildAccessPointFromApiCreateDtoService;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\AccessPointDto;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointByInternalScriptDto;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Client\SelectClientByDomainService;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class InternalAccessPointController implements ApiResource
{
    use EntityManagerAware;

    const RESOURCE_NAME = 'internal_access_points';

    /**
     * @var SelectClientByDomainService
     */
    private $selectClientByDomain;
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

    /**
     * @var ClientService
     */
    private $clientService;

    public function __construct(
        SelectClientByDomainService $selectClientByDomain,
        AccessPointsService $accessPointsService,
        Logger $logger,
        VendorService $vendorService,
        AccessPointApiValidator $accessPointApiValidator,
        AccessPointsGroupsService $accessPointsGroupsService,
        BuildAccessPointFromApiCreateDtoService $buildEntityService,
        ClientService $clientService
    ) {
        $this->selectClientByDomain = $selectClientByDomain;
        $this->accessPointsService = $accessPointsService;
        $this->logger = $logger;
        $this->vendorService = $vendorService;
        $this->accessPointApiValidator = $accessPointApiValidator;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->buildEntityService = $buildEntityService;
        $this->clientService = $clientService;
    }

    public function createAction(Request $request)
    {
        try {
            $body = json_decode($request->getContent(), true);

            if (empty($body)) {
                throw new \InvalidArgumentException("O corpo da requisição está vazio.");
            }

            if (!array_key_exists('client', $body)) {
                return new JsonResponse(['client' => "O campo client é obrigatório."], 400);
            }

            $clientDomain   = array_key_exists('client', $body) ? $body['client'] : null;
            $client         = $this->selectClientByDomain->get($clientDomain);

            if (!$client) {
                return new JsonResponse(['client' => "O cliente informado não foi encontrado."], 400);
            }

            $body['status'] = AccessPoints::ACTIVE;
            $accessPointDto = CreateAccessPointDto::createFromAssocArray($body, $client);
            $accessPointDto->setAction(CreateAccessPointDto::ACTION_INTERNAL_CREATE);
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

    public function loadAccessPoints(Request $request)
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

            $aps = $this->accessPointsService->getAllByStatus(1, $client);

            $apiAccessPoints = AccessPointDto::createFromAccessPointArray($aps);

            if (!$aps) {
                throw new JsonException("Fail to serialize aps message error");
            }

            return new JsonResponse($apiAccessPoints, 200);

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


    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
