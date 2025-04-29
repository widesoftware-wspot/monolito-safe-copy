<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\AdminBundle\Form\ApiTokenType;
use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\ApiWSpotResources;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\ApiWSpot\ApiWSpotService;
use Wideti\DomainBundle\Service\ApiWSpot\Helpers\GetResourceNamesFromTokens;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;


class ApiWSpotController
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use FlashMessageAware;
    use ModuleAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ApiWSpotService
     */
    private $apiWSpotService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * ApiWSpotController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param ApiWSpotService $apiWSpotService
     * @param AnalyticsService $analyticsService
     * @param GetConsentGateway $getConsentGateway
     * @param Auditor $auditor
     */
    public function __construct(
		AdminControllerHelper $controllerHelper,
		ApiWSpotService $apiWSpotService,
		AnalyticsService $analyticsService,
		GetConsentGateway $getConsentGateway,
		Auditor $auditor
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->apiWSpotService = $apiWSpotService;
        $this->analyticsService = $analyticsService;
        $this->getConsentGateway = $getConsentGateway;
        $this->auditor = $auditor;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function indexAction()
    {
        $role = isset($this->moduleService->getUser()->getRoles()[0]) ?
        $this->moduleService->getUser()->getRoles()[0]->getRole() : '';

        if (!$this->moduleService->modulePermission('api')
            && $role !== "ROLE_MARKETING") {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entities = $this->em
            ->getRepository('Wideti\DomainBundle\Entity\ApiWSpot')
            ->getAllByClient($client);

        return $this->render(
            'AdminBundle:ApiWSpot:index.html.twig',
            [
                'entities'      => $entities,
                'resourceNames' => GetResourceNamesFromTokens::getAsString($entities),
                'client'        => $client
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function newAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('api')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->em
            ->getRepository('Wideti\DomainBundle\Entity\Client')
            ->findOneBy(['domain' => $this->getLoggedClient()->getDomain()]);
        $user = $this->controllerHelper->getUser();
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entity = new ApiWSpot();

        $form = $this->controllerHelper->createForm(ApiTokenType::class, $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setClient($client);
            $this->apiWSpotService->create($entity, $request->get('resourceNames'));
            $this->setCreatedFlashMessage();
            $this->analyticsService->handler($request, true);

            $event = $this->auditor->newEvent();
            $event
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->withType(Events::create())
                ->onTarget(Kinds::apiToken(), $entity->getId())
                ->addDescription(AuditEvent::PT_BR, 'Usuário criou token de API e aceitou uso dos dados sob o consentimento')
                ->addDescription(AuditEvent::EN_US,'User created API token and accepted use of data under consent')
                ->addDescription(AuditEvent::ES_ES, 'Token de API creado por el usuario y uso aceptado de datos bajo consentimiento');
            if ($consent->getHasError()) {
                $event->addContext('consent', 'has an error to retrieve consent information: ' . $consent->getError()->getMessage());
            } else {
                $event->addContext('consent_id', $consent->getId())
                    ->addContext('consent_version', $consent->getVersion());
            }
            $this->auditor->push($event);


            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_token'));
        }

        $resourceNames = ApiWSpotResources::getResources();



        return $this->render(
            'AdminBundle:ApiWSpot:form.html.twig',
            [
                'resourceNames' => $resourceNames,
                'resourceNamesChecked' => null,
                'entity' => $entity,
                'form' => $form->createView(),
                'uploadError' => false,
                'consent' => $consent,
                'client' => $client
            ]
        );
    }

    public function editAction(Request $request, ApiWSpot $token)
    {
        if (!$this->moduleService->modulePermission('api')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->em
            ->getRepository('Wideti\DomainBundle\Entity\Client')
            ->findOneBy(['domain' => $this->getLoggedClient()->getDomain()]);

        PlanAssert::checkOrThrow($client, Plan::PRO);

        $token->addPermissionType();

        $form = $this->controllerHelper->createForm(ApiTokenType::class, $token);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->apiWSpotService->update($token, $request->get('resourceNames'));
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_token'));
        }

        $resourceNames = ApiWSpotResources::getResources();
        $resourceNamesChecked = [];

        /**
         * @var ApiWSpotResources $resource
         */
        foreach ($token->getResources() as $resource) {
            array_push($resourceNamesChecked, $resource->getResource());
        }

        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
        return $this->render(
            'AdminBundle:ApiWSpot:form.html.twig',
            [
                'resourceNames'         => $resourceNames,
                'resourceNamesChecked'  => $resourceNamesChecked,
                'entity'                => $token,
                'form'                  => $form->createView(),
                'consent'               => $consent,
                'client'                => $client
            ]
        );
    }

    public function deleteAction(Request $request, ApiWSpot $token)
    {
        if (!$this->moduleService->modulePermission('api')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->apiWSpotService->delete($token);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Não foi possível excluir o Token.'
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_token'));
    }
}
