<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Wideti\ApiBundle\Exception\BulkCreateIsNotArrayException;
use Wideti\ApiBundle\Exception\BulkLimitExceededException;
use Wideti\ApiBundle\Exception\IsNotBulkActionException;
use Wideti\ApiBundle\Service\GuestApiService;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ApiBulkResponseDto;
use Wideti\DomainBundle\Dto\CreateResponseDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\Api\EntityNotFountException;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
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
 * Class GuestApiController
 * @package Wideti\ApiBundle\Controller
 */
class GuestApiController implements ApiResource
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
     */
    public function getCustomFields(Request $request)
    {
        $fields = $this->customFieldsService->getCustomFields();
        return new JsonResponse($fields);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function detail(Request $request)
    {
        $id     = $request->get('id');
        $guest  = $this->guestService->findById($id);

        if (empty($guest)) {
            throw new EntityNotFountException(404, "Visitante não encontrado");
        }

        if ($guest['hasConsentRevoke']) {
            return new JsonResponse(["message"=>"você não tem permissão para acessar esse recurso"], Response::HTTP_FORBIDDEN);
        }

        //Token
        $tokenId = $this->getTokenId($request);
        $client = $this->getClient($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $guest['refId'])
            ->withType(Events::export())
            ->addDescription(AuditEvent::PT_BR, 'API exportou visitante via endpoint detalhe')
            ->addDescription(AuditEvent::EN_US, 'API exported visitor via detail endpoint')
            ->addDescription(AuditEvent::ES_ES, 'Visitante exportado a la API a través del detalle del punto final')
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
        $tokenId = $this->getTokenId($request);
        $client = $this->getClient($request);

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

        if (!is_null($request->get('refId'))) {
            $filters['refId'] = (int) $request->get('refId');
        }

        try {
            $guests = $this->guestService->getAllGuestsPaginated($filters, $limit, $page, $sort);
            // Audit
            foreach ($guests->getElements() as $g) {
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::apiToken(), $tokenId)
                    ->onTarget(Kinds::guest(), $g['refId'])
                    ->withType(Events::export())
                    ->addDescription(AuditEvent::PT_BR, 'API exportou visitante via endpoint de listagem')
                    ->addDescription(AuditEvent::EN_US, 'API exported visitor via listing endpoint')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante exportado a la API a través del punto final de la lista')
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

        // Audit
        $tokenId = $this->getTokenId($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $createdGuest->getMysql())
            ->withType(Events::create())
            ->addDescription(AuditEvent::PT_BR, 'API criou visitante')
            ->addDescription(AuditEvent::EN_US, 'API created guest')
            ->addDescription(AuditEvent::ES_ES, 'Visitante creado por API')
            ->addContext('method', $request->getMethod())
            ->addContext('path', $request->getPathInfo())
            ->addContext('query_params', $this->getQueryParamsAsString($request));
        $this->auditor->push($event);

        if (($createdGuest) && (!empty($data->sendWelcomeSMS)) && ($this->guestApiService->hasPhoneField())) {
            $this->guestApiService->sendSMS($createdGuest, $client, substr($locale, 0, 2));
        }

        return new JsonResponse($createdGuest, 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function bulkCreate(Request $request)
    {
        $locale = $request->getLocale();
        $errorSchema = $this->apiValidator->hasRequiredFields($request, $locale);
        $bulkResponse = new ApiBulkResponseDto();
        $tokenId = $this->getTokenId($request);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, 400);
        }

        $data = json_decode($request->getContent(), false);
        $guests = EntityHelper::structToEntity($data, 'Wideti\DomainBundle\Document\Guest\Guest');
        $client = $this->getClient($request);

        if (!is_array($guests)) {
            throw new BulkCreateIsNotArrayException();
        }

        $bulkLimit = $this->container->getParameter('api_guests_bulk_create_limit');
        if (count($guests) > $bulkLimit) {
            throw new BulkLimitExceededException(
                400,
                "Limite de dados por bulk atingido, envie no máximo {$bulkLimit} visitantes por operação"
            );
        }

        $errors = [];
        /**
         * @var Guest $guest
         */
        foreach ($guests as $guest) {
            $errorsValidation = $this->apiValidator->validate($guest, $locale, ApiSchema::ACTION_CREATE);

            if (count($errorsValidation->getErrors()) > 0) {
                $errors[] = $errorsValidation;
                $bulkResponse->incrementErrorsTotal();
            } else {
                $guest->setId(null);
                $this->guestService->createByApi($guest, $locale, $client);
                $bulkResponse->incrementSuccessTotal();

                // Audit
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::apiToken(), $tokenId)
                    ->onTarget(Kinds::guest(), $guest->getMysql())
                    ->withType(Events::create())
                    ->addDescription(AuditEvent::PT_BR, 'API criou visitante via bulk api')
                    ->addDescription(AuditEvent::EN_US, 'API created visitor via bulk api')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante creado por API a través de API masiva')
                    ->addContext('method', $request->getMethod())
                    ->addContext('path', $request->getPathInfo())
                    ->addContext('query_params', $this->getQueryParamsAsString($request));
                $this->auditor->push($event);

                if (($guest) && (!empty($guest->sendWelcomeSMS)) && ($this->guestApiService->hasPhoneField())) {
                    $this->guestApiService->sendSMS($guest, $client, substr($locale, 0, 2));
                }
            }
        }

        if (count($errors) > 0) {
            $bulkResponse->setErrors($errors);
            $bulkResponse->setHasErrors(true);
            return new JsonResponse($bulkResponse, 200);
        }

        return new JsonResponse($bulkResponse, 200);
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

        $errorsValidation = $this->apiValidator->validate($guest, $locale, ApiSchema::ACTION_UPDATE);

        if (empty($guest->getId())) {
            $err['id'][] = "É necessário um id para atualizar";
            $errorsValidation->addErrors($err);
        }

        if (count($errorsValidation->getErrors()) > 0) {
            return new JsonResponse($errorsValidation, 400);
        }

        $oldGuest = $this->guestService->getUserById($guest->getId());

        if (is_null($oldGuest)) {
            return  new JsonResponse($guest,Response::HTTP_NOT_FOUND);
        }

        if ($oldGuest->isHasConsentRevoke()) {
            return new JsonResponse(["message"=>"você não tem permissão para acessar esse recurso"], Response::HTTP_FORBIDDEN);
        }

        $guest->setMysql($oldGuest->getMysql());

        $client = $this->getClient($request);
        $createdGuest = $this->guestService->updateByApi($guest, $locale, $client);

        // Audit
        $tokenId = $this->getTokenId($request);
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::apiToken(), $tokenId)
            ->onTarget(Kinds::guest(), $guest->getMysql())
            ->withType(Events::create())
            ->addDescription(AuditEvent::PT_BR, 'API atualizou o visitante via api')
            ->addDescription(AuditEvent::EN_US, 'API updated visitor via api')
            ->addDescription(AuditEvent::ES_ES, 'API actualizó al visitante a través de api')
            ->addContext('method', $request->getMethod())
            ->addContext('path', $request->getPathInfo())
            ->addContext('query_params', $this->getQueryParamsAsString($request))
            ->addContext('before', json_encode($oldGuest->getProperties()))
            ->addContext('after', json_encode($createdGuest->getProperties()))
            ;
        $this->auditor->push($event);

        return new JsonResponse($createdGuest, 200);
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
     * @return null|object|Client
     */
    private function getClient(Request $request)
    {
        $domain = StringHelper::getClientDomainByUrl($request->getHttpHost());
        $client = $this->clientService->getClientByDomain($domain);
        return $client;
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
