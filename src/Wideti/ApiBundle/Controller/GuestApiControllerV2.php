<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ORM\EntityManager;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Wideti\ApiBundle\Exception\IsNotBulkActionException;
use Wideti\ApiBundle\Service\GuestApiService;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\CreateResponseDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\Api\EntityNotFountException;
use Wideti\DomainBundle\Helpers\EntityHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\ApiEntityValidator\ApiErrors;
use Wideti\DomainBundle\Service\ApiEntityValidator\ApiValidatorAware;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiSchema;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Client\ClientServiceAware;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;

/**
 * Class GuestApiControllerV2
 * @package Wideti\ApiBundle\Controller
 */
class GuestApiControllerV2 implements ApiResource
{
    use CustomFieldsAware;
    use GuestServiceAware;
    use PaginatorAware;
    use ApiValidatorAware;
    use ClientServiceAware;

    /**
     * @var RecursiveValidator
     */
    private $validator;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var GuestApiService
     */
    private $guestApiService;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * GuestApiController constructor.
     * @param RecursiveValidator $validator
     * @param ContainerInterface $container
     * @param GuestApiService $guestApiService
     * @param Auditor $auditor
     * @param EntityManager $em
     */
    public function __construct(
        RecursiveValidator $validator,
        ContainerInterface $container,
        GuestApiService    $guestApiService,
        Auditor $auditor,
        EntityManager $em
    ) {
        $this->validator       = $validator;
        $this->container       = $container;
        $this->guestApiService = $guestApiService;
        $this->auditor         = $auditor;
        $this->em              = $em;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function detail(Request $request)
    {
        $id     = $request->get('id');
        $guest  = $this->guestService->findById_v2($id);

        if (empty($guest)) {
            throw new EntityNotFountException(404, "Visitante não encontrado");
        }

        if ($guest['hasConsentRevoke']) {
            return new JsonResponse(["message"=>"você não tem permissão para acessar esse recurso"], Response::HTTP_FORBIDDEN);
        }

        // Audit
        $client = $this->getClient($request);
        $tokenId = $this->getTokenId($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $guest['id'])
            ->withType(Events::export())
            ->addDescription(AuditEvent::PT_BR, 'API exportou visitante via detalhe de visitantes V2')
            ->addDescription(AuditEvent::EN_US, 'API exported visitor via V2 visitor detail')
            ->addDescription(AuditEvent::ES_ES, 'API exportou visitante a través de la detalhe de el visitante V2')
            ->addContext('method', $request->getMethod())
            ->addContext('path', $request->getPathInfo())
            ->addContext('query_params', $this->getQueryParamsAsString($request));
        $this->auditor->push($event);

        return new JsonResponse($guest);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function listGuests(Request $request)
    {
        $page    = (int) $request->get('page');
        $limit   = (int) $request->get('limit');
        $sort    = $request->get('sort');
        $filters = [
            'filter' => $request->get('filter'),
            'value'  => $request->get('value'),
            'from'   => $request->get('from'),
            'to'     => $request->get('to'),
        ];

        if (!is_null($request->get('status'))) {
            $filters['status'] = (int) $request->get('status');
        }

        if (!is_null($request->get('id'))) {
            $filters['id'] = (int) $request->get('id');
        }

        try {
            $guests = $this->guestService->getAllGuestsPaginated_v2($filters, $limit, $page, $sort);

            // Audit
            $client = $this->getClient($request);
            $tokenId = $this->getTokenId($request);
            foreach ($guests->getElements() as $g) {
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::apiToken(), $tokenId)
                    ->onTarget(Kinds::guest(), $g['id'])
                    ->withType(Events::export())
                    ->addDescription(AuditEvent::PT_BR, 'API exportou visitante via listagem de visitantes V2')
                    ->addDescription(AuditEvent::EN_US, 'API exported visitor via V2 visitors list')
                    ->addDescription(AuditEvent::ES_ES, 'API exportou visitante a través de la lista de visitante V2')
                    ->addContext('method', $request->getMethod())
                    ->addContext('path', $request->getPathInfo())
                    ->addContext('query_params', $this->getQueryParamsAsString($request));
                $this->auditor->push($event);
            }

            return new JsonResponse($guests);
        } catch (\InvalidArgumentException $e) {
            $apiError = new ApiErrors();
            $apiError->setMessage('Argumentos inválidos no filtro.');
            $apiError->setStatus(400);
            $apiError->setErrors(['filter' => $e->getMessage()]);
            return new JsonResponse($apiError, 400);
        } catch (\Exception $ex) {
            $apiError = new ApiErrors();
            $apiError->setMessage("Ocorreu um erro no servidor.");
            $apiError->setStatus(500);
            $apiError->setErrors(['server_error' => $ex->getMessage()]);
            return new JsonResponse($ex->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function guestDevices(Request $request)
    {
        try {
            $devices = $this->guestService->getGuestDevices($request->get('id'));
            return new JsonResponse($devices);
        } catch (EntityNotFountException $ex) {
            throw new EntityNotFountException(404, "Visitante não encontrado");
        } catch (Forbidden403Exception $ex){
            return new JsonResponse(["message"=>"você não tem permissão para acessar esse recurso"], 403);
        } catch (\Exception $ex) {
            $apiError = new ApiErrors();
            $apiError->setMessage("Ocorreu um erro no servidor.");
            $apiError->setStatus(500);
            $apiError->setErrors(['server_error' => $ex->getMessage()]);
            return new JsonResponse($ex->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request)
    {
        $locale = $request->getLocale();
        $errorSchema = $this->apiValidator->hasRequiredFields($request, $locale);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, 400);
        }

        $data = json_decode($request->getContent(), false);

        /**
         * @var Guest $guest
         */
        $guest = EntityHelper::structToEntity($data, 'Wideti\DomainBundle\Document\Guest\Guest');
        $client = $this->getClient($request);

        if (is_array($guest)) {
            throw new IsNotBulkActionException();
        }

        $errorsValidation = $this->apiValidator->validate($guest, $locale, ApiSchema::ACTION_CREATE);

        if (count($errorsValidation->getErrors()) > 0) {
            return new JsonResponse($errorsValidation, 400);
        }

        $guest->setId(null);
        $createdGuest = $this->guestService->createByApi($guest, $locale, $client);

        if (($createdGuest) && (!empty($data->sendWelcomeSMS)) && ($this->guestApiService->hasPhoneField())) {
            $this->guestApiService->sendSMS($createdGuest, $client, substr($locale, 0, 2));
        }

        $guestCreatedAPIResponseFormat = $this->guestService->findById_v2($createdGuest->getMysql());

        // Audit
        $tokenId = $this->getTokenId($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $guestCreatedAPIResponseFormat['id'])
            ->withType(Events::create())
            ->addDescription(AuditEvent::PT_BR, 'API criou visitante via api V2')
            ->addDescription(AuditEvent::EN_US, 'API created visitor via api V2')
            ->addDescription(AuditEvent::ES_ES, 'Visitante creado por API a través de api V2')
            ->addContext('method', $request->getMethod())
            ->addContext('path', $request->getPathInfo())
            ->addContext('query_params', $this->getQueryParamsAsString($request));
        $this->auditor->push($event);

        return new JsonResponse($guestCreatedAPIResponseFormat, 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function update(Request $request)
    {
        $locale = $request->getLocale();
        $errorSchema = $this->apiValidator->hasRequiredFields($request, $locale, ApiSchema::ACTION_UPDATE);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, 400);
        }

        $data = json_decode($request->getContent(), false);
        $guest = EntityHelper::structToEntity($data, 'Wideti\DomainBundle\Document\Guest\Guest');

        if (is_array($guest)) {
            throw new IsNotBulkActionException();
        }

        $oldGuest = $this->guestService->getGuestByMysql($guest->getId());

        if (is_null($oldGuest)) {
            return  new JsonResponse($guest, Response::HTTP_NOT_FOUND);
        }

        if ($oldGuest->isHasConsentRevoke()) {
            return new JsonResponse(["message"=>"você não tem permissão para acessar esse recurso"], Response::HTTP_FORBIDDEN);
        }

        $guest->setMysql($guest->getId());
        $guest->setId($oldGuest->getId());

        $errorsValidation = $this->apiValidator->validate($guest, $locale, ApiSchema::ACTION_UPDATE);

        if (empty($guest->getId())) {
            $err['id'][] = "É necessário um id para atualizar";
            $errorsValidation->addErrors($err);
        }

        if (count($errorsValidation->getErrors()) > 0) {
            return new JsonResponse($errorsValidation, 400);
        }

        $client = $this->getClient($request);
        $updated = $this->guestService->updateByApi_v2($guest, $locale, $client);

        // Audit
        $tokenId = $this->getTokenId($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $oldGuest->getMysql())
            ->withType(Events::update())
            ->addDescription(AuditEvent::PT_BR, 'API atualizou visitante via api V2')
            ->addDescription(AuditEvent::EN_US, 'API updated guest via api V2')
            ->addDescription(AuditEvent::ES_ES, 'Visitante actualizado de API a través de api V2')
            ->addContext('method', $request->getMethod())
            ->addContext('path', $request->getPathInfo())
            ->addContext('query_params', $this->getQueryParamsAsString($request))
            ->addContext('before', json_encode($oldGuest))
            ->addContext('after', json_encode($updated['updatedEntity']));
        $this->auditor->push($event);

        return new JsonResponse($updated['apiResponse'], 200);
    }

    /**
     * @param Request $request
     * @return null|object|Client
     */
    private function getClient(Request $request)
    {
        $domain = StringHelper::getClientDomainByUrl($request->getHttpHost());
        $client = $this->clientService->getClientByDomain($domain);
        return $client;
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return 'guest';
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getTokenId(Request $request) {
        $token = $request->get('token') ?: $request->headers->get('x-token');
        $apiToken = $this->em
            ->getRepository('DomainBundle:ApiWSpot')
            ->findOneBy([
                'token' => $token
            ]);
        return $apiToken->getId();
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getQueryParamsAsString(Request $request) {
        $params = $request->query->all();
        unset($params['token']);

        $p = array_map(function ($key) use ($params) {
            return "${key}=${params[$key]}";
        }, array_keys($params));

        return join("&", $p);
    }
}
