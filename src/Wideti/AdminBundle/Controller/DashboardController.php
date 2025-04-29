<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\AdminBundle\Form\Type\Reports\DashboardFilterType;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;
use Wideti\DomainBundle\Service\Analytics\Handlers\Custom\GetAdminDashboardHandler;
use Wideti\DomainBundle\Service\Analytics\Handlers\Custom\GetDashboardTabsHandler;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Dashboard\DashboardAware;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class DashboardController
{
    use EntityManagerAware;
    use TwigAware;
    use SessionAware;
    use MongoAware;
    use DashboardAware;
    use ModuleAware;
    use SecurityAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ErpService
     */
    private $erpService;
	/**
	 * @var AnalyticsService
	 */
	private $analyticsService;
    /**
     * @var AccessPointsService
     */
	private $accessPointsService;

	/**
	 * DashboardController constructor.
	 * @param AdminControllerHelper $controllerHelper
	 * @param ConfigurationService $configurationService
	 * @param ErpService $erpService
	 * @param AnalyticsService $analyticsService
     * @param AccessPointsService $accessPointsService
	 */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        ConfigurationService $configurationService,
        ErpService $erpService,
		AnalyticsService $analyticsService,
        AccessPointsService $accessPointsService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->erpService           = $erpService;
	    $this->analyticsService     = $analyticsService;
	    $this->accessPointsService  = $accessPointsService;
    }

	/**
	 * Dashboard Index (load only the page not the tab)All
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 */
    public function dashboardAction(Request $request)
    {
        $this->doLoginEventMetric($request);


    	/**
         * @var $client Client
         */
        $client     = $this->getLoggedClient();
        $this->removeChangePlanHash($client);
        $configMap  = $this->configurationService->getDefaultConfiguration($client);

        $isAuthorizedPlan = PlanAssert::isAuthorizedPlan($client, Plan::PRO);

        $this->session->set('plan', $isAuthorizedPlan);

        $this->configurationService->setOnSession($this->configurationService->getCacheKey('admin'), $configMap);

        $content = $this->dashboardService->home($client, $request->get('dashboardFilter'));

        $filter = $this->controllerHelper->createForm(
            DashboardFilterType::class,
            $request->get('dashboardFilter'),
            [
                'action' => $this->controllerHelper->generateUrl('admin_dashboard')
            ]
        );

        $filter->handleRequest($request);
        $content['filter'] = $filter->createView();
        $pageParams = array_merge($content, $this->dashboardService->overview($client));

        $this->session->set('whiteLabelEnabled', $this->moduleService->modulePermission('white_label'));
        $this->session->set('integrationSsoEnabled', $this->moduleService->modulePermission('sso_integration'));
        $this->session->set('smartLocationEnabled', $this->moduleService->modulePermission('smart_location'));
        $this->session->set('segmentationEnabled', $this->moduleService->modulePermission('segmentation'));
        $this->session->set('surveyEnabled', $this->moduleService->modulePermission('survey'));

        $customerAreaEnabled = false;

        if ($this->moduleService->modulePermission('customer_area') && $client->getType() == Client::TYPE_SIMPLE) {
            $customerAreaEnabled = true;
        }

        $this->session->set('customerAreaEnabled', $customerAreaEnabled);

        $pageParams['allowFakeData'] = false;
        $pageParams['client'] = $client;
        $pageParams['pocEndDate'] = $client->getPocEndDate();
        $user = $this->getUser();

        //ISSO DEVE SER REMOVIDO AO FINAL DA CAMPANHA DE BLOQUIEIO DE CONTEÚDO
        $hasMikrotik = $this->accessPointsService->hasApFromVendor($client, Vendor::MIKROTIK);

        $pageParams['hasMikrotik'] = $hasMikrotik;

        if ($user) {
            $role = $user->getRole();

            if ($role) {
                $pageParams['allowFakeData'] = ($role->getId() === Users::ROLE_MANAGER);
            }
        }
        $this->session->set('host', $request->getHost());
        $isRegularDomain = strpos($request->getHost(), 'wspot.com.br') || strpos($request->getHost(), 'mambowifi');
        $pageParams['isRegularDomain'] = $isRegularDomain;

        $this->analyticsService->handler($request, [
	        'tab'       => GetAdminDashboardHandler::TAB,
	        'filter'    => $filter->getData()
        ]);

        return $this->render(
            'AdminBundle:Dashboard:dashboard.html.twig',
            $pageParams
        );
    }

    /**
     * Dashboard tab: Visão Geral
     */
    public function overviewTabAction()
    {
        return $this->render(
            'AdminBundle:Dashboard:overviewTabStatsBar.html.twig',
            $this->dashboardService->overview($this->getLoggedClient())
        );
    }

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 */
    public function guestsTabAction(Request $request)
    {
	    $client     = $this->session->get('wspotClient');

        $isAuthorizedPlan = PlanAssert::isAuthorizedPlan($client, Plan::PRO);

        $this->session->set('plan', $isAuthorizedPlan);

        $configMap  = $this->configurationService->getDefaultConfiguration($client);
        $content    = $this->dashboardService->guests($this->getLoggedClient());

        $config = [
            'config' => $configMap
        ];

        $pageParams = array_merge($content, $config);

	    $this->analyticsService->handler($request, [
		    'tab'       => GetDashboardTabsHandler::GUESTS_TAB,
		    'filter'    => $this->session->get('dashboardFilterOption'),
		    'refresh'   => $request->cookies->get('dashboardRefresh')
	    ]);

        return $this->render(
            'AdminBundle:Dashboard:guestsTabContent.html.twig',
            $pageParams
        );
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Exception
	 */
    public function networkTabAction(Request $request)
    {
        $client     = $this->session->get('wspotClient');
        $configMap  = $this->configurationService->getDefaultConfiguration($client);
        $content    = $this->dashboardService->network($this->getLoggedClient());

        $config = [
            'config' => $configMap
        ];

        $pageParams = array_merge($content, $config);

	    $this->analyticsService->handler($request, [
		    'tab'       => GetDashboardTabsHandler::NETWORK_TAB,
		    'filter'    => $this->session->get('dashboardFilterOption'),
		    'refresh'   => $request->cookies->get('dashboardRefresh')
	    ]);

        return $this->render(
            'AdminBundle:Dashboard:networkTabContent.html.twig',
            $pageParams
        );
    }

	/**
	 * @param Request $request
	 * @param $tab
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 */
    public function loadDashboardAjaxAction(Request $request, $tab)
    {
        switch ($tab) {
            case 'guests':
                return $this->guestsTabAction($request);
                break;
            case 'network':
                return $this->networkTabAction($request);
                break;
        }
        throw new NotFoundHttpException("Tab not found in Dashboard, something went wrong");
    }

    private function removeChangePlanHash(Client $client)
    {
        if ($client->getStatus() === Client::STATUS_ACTIVE && $client->getChangePlanHash()) {
            $object = $this->em->getRepository('DomainBundle:Client')
                ->findOneBy([
                    'id' => $client->getId()
                ]);

            $object->setChangePlanHash(null);
            $this->em->persist($object);
            $this->em->flush();
        }
    }

    private function doLoginEventMetric(Request $request)
    {
        $datetime  = new \DateTime();
        $sessionId = $datetime->getTimestamp() * 1000;
        $this->session->set("amplitude_session_id", $sessionId);

        $client = $this->getLoggedClient();
        $user   = $this->getUser();

        $hash = "#{$client->getDomain()}#{$user->getUsername()}#logged";
        $logged = $this->session->get($hash);

        if (!$logged) {
            $builder = new EventBuilder();
            $event = $builder
                ->withClientDomain($client->getDomain())
                ->withClientSegment($client->getSegment() ? $client->getSegment()->getName() : 'N/I')
                ->withUserName($user->getNome())
                ->withUserEmail($user->getUsername())
                ->withUserRole($user->getRole()->getName())
                ->withCategory('Painel Administrativo')
                ->withName(GetAdminDashboardHandler::LOGIN)
                ->withEventProperties([])
                ->withSessionId($sessionId)
                ->build();

            $this->analyticsService->sendEvent($event);
            $this->session->set($hash, true);
        }
    }
}
