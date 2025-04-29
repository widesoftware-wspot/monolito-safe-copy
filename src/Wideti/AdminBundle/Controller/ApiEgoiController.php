<?php

namespace Wideti\AdminBundle\Controller;

use Aws\Sns\MessageValidator\Message;
use Aws\Sns\MessageValidator\MessageValidator;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Entity\ApiEgoi;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\ApiEgoi\ApiEgoiServiceAware;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\AdminBundle\Form\ApiEgoiType;

class ApiEgoiController
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use ApiEgoiServiceAware;
    use LoggerAware;
    use CustomFieldsAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
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
     * ApiEgoiController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AnalyticsService $analyticsService
     * @param GetConsentGateway $getConsentGateway
     * @param Auditor $auditor
     */
    public function __construct(
		ConfigurationService $configurationService,
		AdminControllerHelper $controllerHelper,
		CacheServiceImp $cacheService,
		AnalyticsService $analyticsService,
		GetConsentGateway $getConsentGateway,
		Auditor $auditor
    )
    {
        $this->controllerHelper = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->cacheService = $cacheService;
        $this->analyticsService = $analyticsService;
        $this->getConsentGateway = $getConsentGateway;
        $this->auditor = $auditor;
    }

	/**
	 * @param Request $request
	 * @return Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function indexAction(Request $request)
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

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiEgoi:hasntEmailField.html.twig'
            );
        }
        $this->session->remove('apiEgoiId');

        $entities = $this->em->getRepository('DomainBundle:ApiEgoi')->findBy(['client' => $client]);

        return $this->render(
            'AdminBundle:ApiEgoi:index.html.twig',
            [
                'entities'  => $entities,
                'block'     => $request->get('block')
            ]
        );
    }

    public function getListsAction(Request $request)
    {
        $token = $request->get('token');
        $lists = $this->apiEgoiService->getLists($token);

        return new JsonResponse(
            [
                'lists' => $lists
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function createAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('egoi')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiEgoi:hasntEmailField.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

        $apiIntegration = new ApiEgoi();

        $options['attr']['client'] = $client->getId();
        $options['attr']['id'] = null;

        $form = $this->controllerHelper->createForm(
            ApiEgoiType::class,
	        $apiIntegration,
            $options
        );

        $form->handleRequest($request);

        $list = null;
        if ($request->get("wspot_api_egoi") && array_key_exists('list', $request->get("wspot_api_egoi"))) {
            $list = $request->get("wspot_api_egoi")['list'];
            $apiIntegration->setList($list);
        }

        if ($form->isValid() && !is_null($apiIntegration->getList())) {
            $this->apiEgoiService->create($apiIntegration);
            $this->setCreatedFlashMessage();
            $this->analyticsService->handler($request, true);

            // Audit
            $event = $this->auditor->newEvent();
            $event
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->withType(Events::create())
                ->onTarget(Kinds::egoi(), $apiIntegration->getId())
                ->addDescription(AuditEvent::PT_BR, 'Usuário criou integração com E-GOI e aceitou usar os dados de acordo com o termo de consentimento')
                ->addDescription(AuditEvent::EN_US, 'User created integration with E-GOI and agreed to use the data according to the consent form')
                ->addDescription(AuditEvent::ES_ES, 'El usuario creó la integración con E-GOI y acordó usar los datos de acuerdo con el formulario de consentimiento');
            if ($consent->getHasError()) {
                $event->addContext('consent', 'Error on retrieve consent information: ' . $consent->getError()->getMessage());
            } else {
                $event
                    ->addContext('consent_id', $consent->getId())
                    ->addContext('consent_version', $consent->getVersion());
            }
            $this->auditor->push($event);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_egoi'));
        }

        return $this->render(
            'AdminBundle:ApiEgoi:form.html.twig',
            [
                'entity' => $apiIntegration,
                'form' => $form->createView(),
                'manualIntegration' => true,
                'consent' => $consent
            ]
        );
    }

    public function editAction(ApiEgoi $apiEgoi, Request $request)
    {
	    $this->session->set('apiEgoiId', $apiEgoi->getId());

        if (!$this->moduleService->modulePermission('egoi')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
        PlanAssert::checkOrThrow($client, Plan::PRO);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiEgoi:hasntEmailField.html.twig'
            );
        }

        $client = $this->getLoggedClient();

        $options['attr']['client'] = $client->getId();
        $options['attr']['id'] = $apiEgoi->getId();

        $form = $this->controllerHelper->createForm(
            ApiEgoiType::class,
            $apiEgoi,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->apiEgoiService->update($apiEgoi);
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_egoi'));
        }

        return $this->render(
            'AdminBundle:ApiEgoi:form.html.twig',
            [
                'entity' => $apiEgoi,
                'form' => $form->createView(),
                'manualIntegration' => $this->getLoggedClient()->getMadeEgoiIntegration(),
                'consent' => $consent
            ]
        );
    }

    public function deleteAction(ApiEgoi $apiEgoi, Request $request)
    {
        if (!$this->moduleService->modulePermission('egoi')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiEgoi:hasntEmailField.html.twig'
            );
        }

        try {
            $this->apiEgoiService->delete($apiEgoi);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type' => 'msg',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type' => 'msg',
                    'message' => 'Exclusão não permitida'
                ]
            );
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_egoi'));
    }

    public function manualIntegrationAction(Request $request)
    {
        $emailField = $this->customFieldsService->getFieldByNameType('email');

        if (!$emailField) {
            $this->setFlashMessage('notice', 'Não foi possível realizar o envio em massa pois você não possui o campo E-MAIL no formulário de cadastro.');
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('api_egoi')
            );
        }

        $apiToken = $request->get('token');

        if (!$apiToken) {
            $this->setFlashMessage('notice', 'Não foi possível realizar o envio em massa pois o Token está inválido ou não existe.');
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('api_egoi')
            );
        }

        $this->apiEgoiService->sendToSns($apiToken);

        $client = $this->getLoggedClient();
        $client->setMadeEgoiIntegration(true);
        $this->em->merge($client);
        $this->em->flush();

        $this->setFlashMessage('notice', 'Estamos realizando o envio dos visitantes cadastros para sua conta no E-goi. Esse processo pode demorar um pouco...');
        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('api_egoi')
        );
    }

    public function addSubscribeToEgoiAction()
    {
        try {
            $message = Message::fromRawPostData();
            $data = $message->get('Message');

            if ($message->get('Type') == 'SubscriptionConfirmation') {
                $guzzle = new Client();
                $guzzle->request("GET", $message->get('SubscribeURL'));
                header("Status: 200");
                exit;
            }

            $validator = new MessageValidator();
            $validator->validate($message);

            $arrayData = explode("|", $data);
            list($domain, $apiToken) = $arrayData;

            header("Status: 200");
            $this->apiEgoiService->batchAllGuestsSubscribe($domain, $apiToken);
        } catch (\Exception $ex) {
            $this->logger->addCritical('Fail to execute batch conversions on E-goi', [
                'client'  => $domain,
                'token'   => $apiToken,
                'message' => $ex->getMessage()
            ]);
        }

        return new Response("Conversoes enviadas ao E-goi", 200);
    }
}
