<?php

namespace Wideti\AdminBundle\Controller;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Entity\ApiRDStation;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\ApiRDStation\ApiRDStationServiceAware;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\IntegrationValidator\Dto\IntegrationValidate;
use Wideti\DomainBundle\Service\IntegrationValidator\IntegrationValidatorInterface;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\AdminBundle\Form\ApiRDStationType;
/**
 * Class ApiRDStationController
 * @package Wideti\AdminBundle\Controller
 * ### DOCUMENTATION ###
 * url: https://wideti.atlassian.net/wiki/spaces/DES/pages/471203845/Integra+o+com+RD+Station+-+Fluxo+da+integra+o
 */
class ApiRDStationController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use ApiRDStationServiceAware;
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
     * @var IntegrationValidatorInterface
     */
    private $integrationValidator;

    /**
     * ApiRDStationController constructor.
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
		Auditor $auditor,
        IntegrationValidatorInterface $integrationValidator
    )
    {
        $this->controllerHelper = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->cacheService = $cacheService;
        $this->analyticsService = $analyticsService;
        $this->getConsentGateway = $getConsentGateway;
        $this->auditor = $auditor;
        $this->integrationValidator = $integrationValidator;
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
                'AdminBundle:ApiRDStation:hasntEmailField.html.twig'
            );
        }
        $this->session->remove('apiRDStationId');

        $entities = $this->em->getRepository('DomainBundle:ApiRDStation')->findBy(['client' => $client]);

        return $this->render(
            'AdminBundle:ApiRDStation:index.html.twig',
            [
                'entities' => $entities,
                'block' => $request->get('block'),
                'client' => $client
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
        if (!$this->moduleService->modulePermission('rd_station')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiRDStation:hasntEmailField.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

        $rdIntegration = new ApiRDStation();

        $options['attr']['client'] = $client->getId();
        $options['attr']['id'] = null;

        $form = $this->controllerHelper->createForm(
            ApiRDStationType::class,
            $rdIntegration,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var IntegrationValidate $integrationValidate
             */
            $integrationValidate = $this->integrationValidator->validate($rdIntegration->getToken());
            if ($integrationValidate->isValid()){
                $this->apiRDStationService->create($rdIntegration);
                $this->setCreatedFlashMessage();
                $this->analyticsService->handler($request, true);

                // Audit
                $event = $this->auditor->newEvent();
                $event
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->withType(Events::create())
                    ->onTarget(Kinds::rdStation(), $rdIntegration->getId())
                    ->addDescription(AuditEvent::PT_BR, 'Usuário criou integração com RD Station e aceitou o uso baseado no termo de consentimento')
                    ->addDescription(AuditEvent::EN_US, 'User created integration with RD Station and accepted the use based on the consent form')
                    ->addDescription(AuditEvent::ES_ES, 'El usuario creó la integración con RD Station y aceptó el uso según el formulario de consentimiento');
                if ($consent->getHasError()) {
                    $event->addContext('consent', 'Error on retrieve consent information: ' . $consent->getError()->getMessage());
                } else {
                    $event
                        ->addContext('consent_id', $consent->getId())
                        ->addContext('consent_version', $consent->getVersion());
                }

                $this->auditor->push($event);

                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_rd_station'));
            }
            return $this->render(
                'AdminBundle:ApiRDStation:form.html.twig',
                [
                    'error_msg' => $integrationValidate->getErrorMessage(),
                    'entity' => $rdIntegration,
                    'form' => $form->createView(),
                    'manualIntegration' => true,
                    'consent' => $consent
                ]
            );

        }

        return $this->render(
            'AdminBundle:ApiRDStation:form.html.twig',
            [
                'entity' => $rdIntegration,
                'form' => $form->createView(),
                'manualIntegration' => true,
                'consent' => $consent
            ]
        );
    }

    public function editAction(ApiRDStation $apiRDStation, Request $request)
    {
        $this->session->set('apiRDStationId', $apiRDStation->getId());

        if (!$this->moduleService->modulePermission('rd_station')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiRDStation:hasntEmailField.html.twig'
            );
        }

        $client = $this->getLoggedClient();

        $options['attr']['client'] = $client->getId();
        $options['attr']['id'] = $apiRDStation->getId();

        $form = $this->controllerHelper->createForm(
            ApiRDStationType::class,
	        $apiRDStation,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var IntegrationValidate $integrationValidate
             */
            $integrationValidate = $this->integrationValidator->validate($apiRDStation->getToken());
            if ($integrationValidate->isValid()){
                $this->apiRDStationService->update($apiRDStation);
                $this->setUpdatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_rd_station'));
            }

            return $this->render(
                'AdminBundle:ApiRDStation:form.html.twig',
                [
                    'error_msg' => $integrationValidate->getErrorMessage(),
                    'entity' => $apiRDStation,
                    'form' => $form->createView(),
                    'manualIntegration' => $this->getLoggedClient()->getMadeRdIntegration(),
                    'consent' => $consent
                ]
            );
        }

        return $this->render(
            'AdminBundle:ApiRDStation:form.html.twig',
            [
                'entity' => $apiRDStation,
                'form' => $form->createView(),
                'manualIntegration' => $this->getLoggedClient()->getMadeRdIntegration(),
                'consent' => $consent
            ]
        );
    }

    public function deleteAction(ApiRDStation $apiRDStation, Request $request)
    {
        if (!$this->moduleService->modulePermission('rd_station')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        if (!$this->customFieldsService->getFieldByNameType('email')) {
            return $this->render(
                'AdminBundle:ApiRDStation:hasntEmailField.html.twig'
            );
        }

        try {
            $this->apiRDStationService->delete($apiRDStation);

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

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('api_rd_station'));
    }

    public function manualIntegrationAction(Request $request)
    {
        $emailField = $this->customFieldsService->getFieldByNameType('email');

        if (!$emailField) {
            $this->setFlashMessage('notice', 'Não foi possível realizar o envio em massa pois você não possui o campo E-MAIL no formulário de cadastro.');
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('api_rd_station')
            );
        }

        $rdStationToken = $request->get('token');

        if (!$rdStationToken) {
            $this->setFlashMessage('notice', 'Não foi possível realizar o envio em massa pois o Token está inválido ou não existe.');
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('api_rd_station')
            );
        }

        $this->apiRDStationService->sendToSns($rdStationToken);

        $client = $this->getLoggedClient();
        $client->setMadeRdIntegration(true);
        $this->em->merge($client);
        $this->em->flush();

        $this->setFlashMessage('notice', 'Estamos realizando o envio dos visitantes cadastros para sua conta na RD Station. Esse processo pode demorar um pouco...');

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('api_rd_station')
        );
    }

    public function sendConversionsToRDAction()
    {
        try {
            $message = Message::fromRawPostData();
            $data = $message->toArray();

            if ($data['Type'] == 'SubscriptionConfirmation') {
                $guzzle = new Client();
                $guzzle->request("GET", $data['SubscribeURL']);
                header("Status: 200");
                exit;
            }

            $validator = new MessageValidator();
            $validator->validate($message);

            $arrayData = explode("|", $data['Message']);
            list($domain, $rdStationToken) = $arrayData;

            header("Status: 200");
            $this->apiRDStationService->batchAllGuestsConversions($domain, $rdStationToken);

        } catch (\Exception $ex) {
            $this->logger->addCritical('Fail to execute batch conversions on RD Station', [
                'client' => $this->getLoggedClient()->getDomain(),
                'message' => $ex->getMessage()
            ]);
        }

        return new Response("Conversoes enviadas ao RD", 200);
    }
}
