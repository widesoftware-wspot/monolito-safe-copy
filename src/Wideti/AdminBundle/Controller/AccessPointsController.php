<?php

namespace Wideti\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\AdminBundle\Form\AccessPointsType;
use Wideti\AdminBundle\Form\Type\AccessPoints\AccessPointsFilterType;
use Wideti\AdminBundle\Form\Type\AccessPoints\AccessPointsImportType;
use Wideti\DomainBundle\Entity\AccessPointMonitoring;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\ClientsControllersUnifi;
use Wideti\DomainBundle\Entity\ControllersUnifi;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Exception\ControllerUnifiUniqueException;
use Wideti\DomainBundle\Exception\WrongAccessPointIdentifierException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\Pagination;
use Wideti\DomainBundle\Repository\DeskbeeDeviceRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\AccessPointMonitoring\Monitoring;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsExtraConfigService;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsServiceAware;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\SitesBlocking;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\BulkInsert\BulkInsertService;
use Wideti\DomainBundle\Service\ClientsControllersUnifi\ClientsControllersUnifiService;
use Wideti\DomainBundle\Service\ControllersUnifi\ControllersUnifiService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Vendor\VendorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class AccessPointsController
{
    use EntityManagerAware;
    use TwigAware;
    use AccessPointsServiceAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use RadacctRepositoryAware;
    use VendorAware;
    use LoggerAware;
    use FlashMessageAware;
    use ContainerAwareTrait;
    use SecurityAware;
    use ModuleAware;

    private $grafanaUrl;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var BulkInsertService
     */
    private $bulkInsertService;
    /**
     * @var TimezoneService
     */
    private $timezoneService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var Monitoring
     */
    private $accessPointMonitoring;
    /**
     * @var SitesBlocking
     */
    private $sitesBlocking;

    /**
     * @var AccessPointsExtraConfigService
     */
    private $accessPointsExtraConfigService;

    /**
     * @var DeskbeeDeviceRepository
     */
    private $deskbeeDeviceRepository;

    /**
     * @var ControllersUnifiService
     */
    private $controllersUnifiService;

    /**
     * @var ClientsControllersUnifiService
     */
    private $clientsControllersUnifiService;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * AccessPointsController constructor.
     * @param $grafanaUrl
     * @param AdminControllerHelper $controllerHelper
     * @param BulkInsertService $bulkInsertService
     * @param TimezoneService $timezoneService
     * @param AnalyticsService $analyticsService
     * @param Monitoring $accessPointMonitoring
     * @param SitesBlocking $sitesBlocking
     * @param AccessPointsExtraConfigService $accessPointsExtraConfigService
     * @param DeskbeeDeviceRepository $deskbeeDeviceRepository
     * @param ControllersUnifiService $controllersUnifiService
     */
    public function __construct(
        $grafanaUrl,
        AdminControllerHelper $controllerHelper,
        BulkInsertService $bulkInsertService,
        TimezoneService $timezoneService,
        AnalyticsService $analyticsService,
        Monitoring $accessPointMonitoring,
        SitesBlocking $sitesBlocking,
        AccessPointsExtraConfigService $accessPointsExtraConfigService,
        DeskbeeDeviceRepository $deskbeeDeviceRepository,
        ControllersUnifiService $controllersUnifiService,
        ClientsControllersUnifiService $clientsControllersUnifiService
    ) {
        $this->grafanaUrl = $grafanaUrl;
        $this->controllerHelper = $controllerHelper;
        $this->bulkInsertService = $bulkInsertService;
        $this->timezoneService = $timezoneService;
        $this->analyticsService = $analyticsService;
        $this->accessPointMonitoring = $accessPointMonitoring;
        $this->sitesBlocking = $sitesBlocking;
        $this->accessPointsExtraConfigService = $accessPointsExtraConfigService;
        $this->deskbeeDeviceRepository = $deskbeeDeviceRepository;
        $this->controllersUnifiService = $controllersUnifiService;
        $this->clientsControllersUnifiService = $clientsControllersUnifiService;
    }

    /**
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page, Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client     = $this->getLoggedClient();
        $value      = null;
        $status     = null;
        $btnCancel  = false;

        $filterForm = $this->controllerHelper->createForm(
            AccessPointsFilterType::class,
            null,
            [
                'action' => $this->controllerHelper->generateUrl('access_points')
            ]
        );

        $filterForm->handleRequest($request);

        $count_active  = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client->getId(), [
                'status' => AccessPoints::ACTIVE
            ]);

        $count_inactive  = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client->getId(), [
                'status' => AccessPoints::INACTIVE
            ]);

        if ($filterForm->isValid()) {
            $value  = $filterForm->get('value')->getData();
            $status = $filterForm->get('status')->getData();

            $filter = [
                'value'  => $value,
                'status' => $status
            ];

            $btnCancel = true;

            $count  = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->count($this->getLoggedClient(), $filter);

            $pagination       = new Pagination($page, $count);
            $pagination_array = $pagination->createPagination();

            $entities = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->listAll(
                    $this->getLoggedClient(),
                    $pagination->getPerPage(),
                    $pagination_array['offset'],
                    $filter
                );

            return $this->render(
                'AdminBundle:AccessPoints:index.html.twig',
                [
                    'entities'              => $entities,
                    'pagination'            => $pagination_array,
                    'form'                  => $filterForm->createView(),
                    'count_active_aps'      => $count_active,
                    'count_inactive_aps'    => $count_inactive,
                    'count_contracted_aps'  => $client->getContractedAccessPoints(),
                    'btnCancel'             => $btnCancel,
                    'monitoringIsActive'    => $this->moduleService->modulePermission('access_point_monitoring'),
                    'sitesBlockingIsActive' => $this->moduleService->modulePermission('sites_blocking'),
                ]
            );
        }

        $pagination       = new Pagination($page, $count_active);
        $pagination_array = $pagination->createPagination();

        $accessPointsEntity = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->listAll(
                $client->getId(),
                $pagination->getPerPage(),
                $pagination_array['offset'],
                [
                    'status' => AccessPoints::ACTIVE
                ]
            );

        return $this->render(
            'AdminBundle:AccessPoints:index.html.twig',
            [
                'entities'              => $accessPointsEntity,
                'pagination'            => $pagination_array,
                'form'                  => $filterForm->createView(),
                'count_active_aps'      => $count_active,
                'count_inactive_aps'    => $count_inactive,
                'count_contracted_aps'  => $client->getContractedAccessPoints(),
                'btnCancel'             => $btnCancel,
                'monitoringIsActive'    => $this->moduleService->modulePermission('access_point_monitoring'),
                'sitesBlockingIsActive' => $this->moduleService->modulePermission('sites_blocking'),
                'status'                => 'cadastrados'
            ]
        );
    }

    /**
     * @param Request $request
     * @param AccessPointsGroups|null $group
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function newAction(Request $request, AccessPointsGroups $group = null)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();

        if ($this->accessPointsService->isReachedLimit($client)) {
            return $this
                ->controllerHelper
                ->redirect($this->controllerHelper->generateUrl('access_points_limit_reached'));
        }

        $entity = new AccessPoints();
        $options = [];

        if ($group === null) {
            $options = [
                'show_group' => true
            ];
        }

        $deskbeeIntegration = $this->moduleService->modulePermission('deskbee_integration');
        $heatMapModuleActive = $this->moduleService->modulePermission('heatmap');
        $options['heatmap_module_active'] = $heatMapModuleActive;
        $options['deskbee_integration'] = $deskbeeIntegration;
        $options['attr']['client'] = $client->getId();
        $options['enable_disconnect_guest']  = $this->moduleService->modulePermission('disconnect_guest');

        $form   = $this->controllerHelper->createForm(AccessPointsType::class, $entity, $options);
        $form->handleRequest($request);

        $brazilianTimezones = $this->timezoneService->getAllBrazilianTimezones();
        $timezonesExceptBrazilian = $this->timezoneService->getAllTimezonesExceptBrazilian();
        $extraConfigValue = null;

        if ($form->isValid()) {
            $timezone = $request->get('timezone');
            $entity->setTimezone($timezone);

            $extraConfig = $request->get('wspot_access_point')['extraConfig'];

            if($heatMapModuleActive && $request->get('wspot_access_point')['shouldGetCoords']) {
                $coords = $this->getCoordsByAddress($entity->getLocal());
                if ($coords) {
                    $entity->setLocation($coords);
                }
            }

            $isValid = true;
            try {
                $this->accessPointsService->create($entity, $group);
                if ($deskbeeIntegration) {
                    $this->deskbeeDeviceRepository->setAccessPoint($entity);
                }
                if ($extraConfig){

                    $this->accessPointsExtraConfigService->create($entity, $extraConfig);
                }
                $this->setCreatedFlashMessage();
            } catch (WrongAccessPointIdentifierException $e) {
                $isValid = false;
                $form->get('identifier')
                    ->addError(new FormError($e->getMessage()));
            } catch (\Exception $e) {
                $isValid = false;

                if ($e->getMessage() == 'unique_identifier') {
                    $form->get('identifier')
                        ->addError(new FormError('Valor já cadastrado.'));
                } else {
                    $form->get('identifier')
                        ->addError(new FormError("Error to create Access Point: {$e->getMessage()}"));
                    $this->logger->addCritical("Error to create Access Point: {$e->getMessage()}");
                }
            }

            if ($isValid === true) {
                $this->analyticsService->handler($request, true);

                if ($group === null) {
                    return $this
                        ->controllerHelper
                        ->redirect($this->controllerHelper->generateUrl('access_points'));
                }

                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl(
                        'access_points_groups_edit',
                        [
                            'id' => $group->getId()
                        ]
                    )
                );
            }
        }

        // get domain
		$configDomain = "mambowifi.com";
		if ($client->isWhiteLabel()) {
			$splitDomain = explode(".", $client->getDomain());
			unset($splitDomain[0]);
			$configDomain = join(".", $splitDomain);
		}

        // get controller
        $controllerUnifi;
        try {
            $controllerUnifi = $this->controllersUnifiService->getControllerByClientId($client->getId());
        } catch (\Exception $error) {
            $form->get('identifier')
                ->addError(new FormError("Error to get Controller Unifi data: {$error->getMessage()}"));
        }

        $unifiControllersMambo;
        try {
            $unifiControllersMambo = $this->controllersUnifiService->getAllUnifiControllersMamboActive();
        } catch (\Exception $error) {
            $form->get('identifier')
                ->addError(new FormError("Error to get Controller Unifi Mambo data: {$error->getMessage()}"));
        }
        

        $urlCtrlUnifiSaveEdit = $this->controllerHelper->generateUrl('access_points_unifi_add');
        if (count($controllerUnifi) > 0) {
            $urlCtrlUnifiSaveEdit = $this->controllerHelper->generateUrl('access_points_unifi_update');
            $controllerUnifi = $controllerUnifi[0];
            if ($controllerUnifi->is_mambo) {
                $controllerUnifi->setAddress("");
                $controllerUnifi->setPort("");
                $controllerUnifi->setUsername("");
                $controllerUnifi->setPassword("");
            }
        }

        return $this->render(
            'AdminBundle:AccessPoints:form.html.twig',
            [
                'entity'                    => $entity,
                'brazilianTimezones'        => $brazilianTimezones,
                'timezonesExceptBrazilian'  => $timezonesExceptBrazilian,
                'newAction'                 => true,
                'form'                      => $form->createView(),
                'group'                     => $group,
                'vendors'                   => json_encode($this->vendor->getVendorsToView()),
                'client'                    => $client,
				'configDomain'				=> $configDomain,
                'extraConfig'               => $extraConfigValue,
                'controllerUnifi'           => $controllerUnifi,
                'urlCtrlUnifiSaveEdit'      => $urlCtrlUnifiSaveEdit,
                'unifiControllersMambo'     => $unifiControllersMambo,
                'urlCtrlUnifiValidate'      => $this->controllerHelper->generateUrl('access_points_unifi_validate')
            ]
        );
    }

    private function getCoordsByAddress($address) {

        if ($address) {
            $options = [
                'http' => [
                    'header' => "User-Agent: Wspot/1.0\n"
                ]
            ];
            $context = stream_context_create($options);
            $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);

            $response = file_get_contents($url, false, $context);
            $data = json_decode($response, true);

            if (!empty($data)) {
                $latitude = $data[0]['lat'];
                $longitude = $data[0]['lon'];

                return $latitude . ", " . $longitude;
            }
        }
    }

    /**
     * @param Request $request
     * @param AccessPoints $station
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @ParamConverter(
     *      "station",
     *      class       = "DomainBundle:AccessPoints",
     *      converter   = "client",
     *      options     = {"message" = "Ponto de acesso não encontrado."}
     * )
     */
    public function editAction(Request $request, AccessPoints $station)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client     = $this->getLoggedClient();
        $localSaved = $station->getLocal();
        $vendor     = $station->getVendor();
        $identifier = $station->getIdentifier();
        $hasAccess  = $this->radacctRepository->checkIfAccessPointHasAccess($client->getId(), $station);

        $options = [
            'show_group' => true,
            'attr' => ['is_update' => true]
        ];

        $deskbeeIntegration = $this->moduleService->modulePermission('deskbee_integration');
        $options['deskbee_integration'] = $deskbeeIntegration;
        $heatMapModuleActive = $this->moduleService->modulePermission('heatmap');
        $options['heatmap_module_active'] = $heatMapModuleActive;
        if ($deskbeeIntegration) {
            $this->deskbeeDeviceRepository
                ->getOrCreateDeskbeeDevice($station);
        }
        $options['attr']['client'] = $client->getId();
        $options['enable_disconnect_guest']  = $this->moduleService->modulePermission('disconnect_guest');

        $form   = $this->controllerHelper->createForm(AccessPointsType::class, $station, $options);

        $form->handleRequest($request);

        $brazilianTimezones = $this->timezoneService->getAllBrazilianTimezones();
        $timezonesExceptBrazilian = $this->timezoneService->getAllTimezonesExceptBrazilian();

        $extraConfig = null;
        $extraConfigValue = null;

        if(in_array($vendor, [Vendor::RUCKUS_CLOUD, Vendor::TP_LINK_CLOUD, Vendor::TP_LINK_V4_CLOUD,Vendor::TP_LINK_V5_CLOUD, Vendor::UNIFI_UBIQUITI])) {
            $extraConfig = $this->accessPointsExtraConfigService->findExtraConfigByAp($station);
            if (!is_null($extraConfig)) {
                $extraConfigValue = $extraConfig->getValue();
            }
        }

        if ($form->isValid()) {
            $timezone = $request->get('timezone');
            $vendor = ($vendor) ?: $station->getVendor();
            $uow    = $this->em->getUnitOfWork();
            $originalEntity = $uow->getOriginalEntityData($station);
            $extraConf = $request->get('wspot_access_point')['extraConfig'];
            $station->setVendor($vendor);
            if($heatMapModuleActive && $request->get('wspot_access_point')['shouldGetCoords'] && $station->getLocal() != $localSaved) {
                $coords = $this->getCoordsByAddress($station->getLocal());
                if ($coords) {
                    $station->setLocation($coords);
                }
            }

            if(in_array($vendor, [Vendor::RUCKUS_CLOUD, Vendor::TP_LINK_CLOUD, Vendor::TP_LINK_V4_CLOUD,Vendor::TP_LINK_V5_CLOUD, Vendor::UNIFI_UBIQUITI])) {
                $extraConfig  =  $this->accessPointsExtraConfigService->findExtraConfigByAp($station);
                if (is_null($extraConfig)) {
                    $this->accessPointsExtraConfigService->create($station, $extraConf);
                } else {
                    $extraConfig->setValue($extraConf);
                    $this->accessPointsExtraConfigService->update($extraConfig);
                }
            }

            if ($originalEntity['status'] == false && $station->getStatus() == 1) {
                if ($this->accessPointsService->isReachedLimit($client)) {
                    return $this
                        ->controllerHelper
                        ->redirect($this->controllerHelper->generateUrl('access_points_limit_reached'));
                }
            }

            $station->setIdentifier($identifier);
            $station->setTimezone($timezone);

            $this->accessPointsService->update($station);
            $this->setUpdatedFlashMessage();
            $this->analyticsService->handler($request, true);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_points'));
        }

        $domain = $client->getDomain();

        // get domain
        $configDomain = "wspot.com.br";
        if ($client->isWhiteLabel()) {
            $splitDomain = explode(".", $client->getDomain());
            unset($splitDomain[0]);
            $configDomain = join(".", $splitDomain);
        }

        $controllerUnifi;
        try {
            $controllerUnifi = $this->controllersUnifiService->getControllerByClientId($client->getId());
        } catch (\Exception $error) {
            $form->get('identifier')
                ->addError(new FormError("Error to get Controller Unifi data: {$error->getMessage()}"));
        }

        $unifiControllersMambo;
        try {
            $unifiControllersMambo = $this->controllersUnifiService->getAllUnifiControllersMamboActive();
        } catch (\Exception $error) {
            $form->get('identifier')
                ->addError(new FormError("Error to get Controller Unifi Mambo data: {$error->getMessage()}"));
        }
        

        $urlCtrlUnifiSaveEdit = $this->controllerHelper->generateUrl('access_points_unifi_add');
        if (count($controllerUnifi) > 0) {
            $urlCtrlUnifiSaveEdit = $this->controllerHelper->generateUrl('access_points_unifi_update');
            $controllerUnifi = $controllerUnifi[0];
            if ($controllerUnifi->is_mambo) {
                $controllerUnifi->setAddress("");
                $controllerUnifi->setPort("");
                $controllerUnifi->setUsername("");
                $controllerUnifi->setPassword("");
            }
        }

        return $this->render(
            'AdminBundle:AccessPoints:form.html.twig',
            [
                'hasAccess'                 => (bool)$hasAccess,
                'brazilianTimezones'        => $brazilianTimezones,
                'timezonesExceptBrazilian'  => $timezonesExceptBrazilian,
                'newAction'                 => false,
                'entity'                    => $station,
                'form'                      => $form->createView(),
                'group'                     => null,
                'vendors'                   => json_encode($this->vendor->getVendorsToView()),
                'domain'                    => $domain,
                'client'                    => $client,
                'configDomain'              => $configDomain,
                'extraConfig'               => $extraConfigValue,
                'controllerUnifi'           => $controllerUnifi,
                'urlCtrlUnifiSaveEdit'      => $urlCtrlUnifiSaveEdit,
                'unifiControllersMambo'     => $unifiControllersMambo,
                'urlCtrlUnifiValidate'      => $this->controllerHelper->generateUrl('access_points_unifi_validate')
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function importAction(Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        $form = $this->controllerHelper->createForm(AccessPointsImportType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $files  = $request->files->get('wspot_access_point_import');
                /**
                 * @var UploadedFile $file
                 */
                $file   = $files['fileUpload'];

                if (!$file) {
                    throw new \Exception("Arquivo não selecionado.");
                }

                $bulkResponse = $this->bulkInsertService->process($file, $client);

                return $this->render(
                    'AdminBundle:AccessPoints:import.html.twig',
                    [
                        'form'      => $form->createView(),
                        'response'  => $bulkResponse
                    ]
                );
            } catch (\Exception $ex) {
                return $this->render(
                    'AdminBundle:AccessPoints:import.html.twig',
                    [
                        'form'      => $form->createView(),
                        'fatalError'  => $ex->getMessage()
                    ]
                );
            }
        }

        return $this->render(
            'AdminBundle:AccessPoints:import.html.twig',
            [
                'form'      => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param AccessPoints $station
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     * @ParamConverter(
     *      "station",
     *      class       = "DomainBundle:AccessPoints",
     *      converter   = "client",
     *      options     = {"message" = "Ponto de acesso não encontrado."}
     * )
     */
    public function deleteAction(Request $request, AccessPoints $station)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();

        $hasAccess = (bool)$this->radacctRepository->checkIfAccessPointHasAccess($client->getId(), $station);

        if ($hasAccess === true) {
            return new JsonResponse(['error' => 'Exclusão de Access Point não permitida.']);
        }

        if ($station->getVendor() == Vendor::MIKROTIK) {
            $this->accessPointMonitoring->removeDashboard($station->getId());
        }

        if (in_array($station->getVendor(), [Vendor::RUCKUS_CLOUD, Vendor::TP_LINK_CLOUD, Vendor::TP_LINK_V4_CLOUD,Vendor::TP_LINK_V5_CLOUD])) {
            $this->accessPointsExtraConfigService->deleteExtraConfig($station);
        }

        $deskbeeDevice = $this->deskbeeDeviceRepository->getDeskbeeDeviceByAccessPoint($station);
        if ($deskbeeDevice) {
          $this->deskbeeDeviceRepository->deleteDevice($deskbeeDevice);
        }

        $this->accessPointsService->delete($station);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Registro removido com sucesso']);
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manualAction(Request $request)
    {
        $vendors = $this->vendor->getVendorsToView();
        $vendor  = $request->get('vendor');

        if ($vendor) {
            if (isset($vendors[$vendor]['manual'])) {
                return $this->render(
                    'AdminBundle:AccessPoints:manual.html.twig',
                    [
                        'manual' => $vendors[$vendor]['manual']
                    ]
                );
            }
        }

        return $this->render('AdminBundle:AccessPoints:manual.html.twig');
    }

    /**
     * @param AccessPoints $accessPoint
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function monitoringAction(AccessPoints $accessPoint)
    {
        if (!$this->moduleService->modulePermission('access_point_monitoring')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        /**
         * @var AccessPointMonitoring $dashboard
         */
        $dashboard = $this->accessPointMonitoring->getDashboard($accessPoint);

        return $this->render('AdminBundle:AccessPoints:monitoring.html.twig', [
            'accessPoint'   => $accessPoint,
            'grafanaUrl'    => $this->grafanaUrl,
            'graphs'        => $dashboard->getPanels()
        ]);
    }

    public function sitesBlockingAction(AccessPoints $accessPoint)
    {
        if (!$this->moduleService->modulePermission('sites_blocking')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        try {
            $reports = $this->sitesBlocking->report($accessPoint);
        } catch (\Exception $ex) {
            $this->logger->addCritical(
                "Sites Blocking Report - Fail to request report microservice API",
                [
                    'error' => $ex->getMessage()
                ]
            );

            return $this->render(
                'AdminBundle:Admin:error.html.twig'
            );
        }

        return $this->render('AdminBundle:AccessPoints:sites-blocking.html.twig', [
            'accessPoint'   => $accessPoint,
            'reports'       => $reports
        ]);
    }

    /**
     * @param FileUpload $fileUpload
     */
    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function limitReachedAction()
    {
        return $this->render('AdminBundle:AccessPoints:limitReached.html.twig');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function unifiAdd(Request $request)
    {
        $clientId = $this->getLoggedClient()->getId();
        $isMambo = $request->get("isMambo");
        if ($isMambo === '1') {
            $idCtrlMambo = $request->get("idCtrlMambo");
        }
        else {
            $address = $request->get("address");
            if (substr($address, -1) === "/" || substr($address, -1) === "\\") {
                $address = substr($address, 0, -1);
            }
            $port = $request->get("port");
            $username = $request->get("username");
            $password = $request->get("password");
            
            $controllerUnifi = new ControllersUnifi();
            $controllerUnifi->setAddress($address);
            $controllerUnifi->setPort($port);
            $controllerUnifi->setUsername($username);
            $controllerUnifi->setPassword($password);
            $controllerUnifi->setIsMambo($isMambo);
        }
        
        $arrResponse = [];
        try {
            if ($isMambo === '1') {
                $clientController = new ClientsControllersUnifi($idCtrlMambo, $clientId);
                $this->clientsControllersUnifiService->create($clientController);
            }
            else {
                $this->controllersUnifiService->create($controllerUnifi, $clientId);
            }
            $arrResponse = ["code" => "201", "msg" => "Controller cadastrada com sucesso!"];
        } catch (ControllerUnifiUniqueException $e) {
            $arrResponse = ["code" => "400", "error" => $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getMessage() == 'uk_controllers_unifi') {
                $arrResponse = ["code" => "400", "error" => "Controller já cadastrada!"];
            } else {
                $arrResponse = ["code" => "500", "error" => "Erro ao criar controller: {$e->getMessage()}"];
                $this->logger->addCritical("Error to create Controller: {$e->getMessage()}");
            }
        }
        
        return new JsonResponse($arrResponse);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function unifiUpdate(Request $request)
    {
        $clientId = $this->getLoggedClient()->getId();
        $id = $request->get("id");
        $isMambo = $request->get("isMambo");
        if ($isMambo === '1') {
            $idCtrlMambo = $request->get("idCtrlMambo");
        }
        else {
            $address = $request->get("address");
            if (substr($address, -1) === "/" || substr($address, -1) === "\\") {
                $address = substr($address, 0, -1);
            }
            $port = $request->get("port");
            $username = $request->get("username");
            $password = $request->get("password");
            
            $controllerUnifi = new ControllersUnifi();
            $controllerUnifi->setId($id);
            $controllerUnifi->setIsMambo($isMambo);
            $controllerUnifi->setAddress($address);
            $controllerUnifi->setPort($port);
            $controllerUnifi->setUsername($username);
            $controllerUnifi->setPassword($password);
        }
        
        $arrResponse = [];
        try {
            if ($isMambo === '1') {
                $clientController = new ClientsControllersUnifi($idCtrlMambo, $clientId);
                $this->clientsControllersUnifiService->create($clientController);
            }
            else {
                $this->controllersUnifiService->update($controllerUnifi, $clientId);
            }
            $arrResponse = ["code" => "201", "msg" => "Controller cadastrada com sucesso!"];
        } catch (ControllerUnifiUniqueException $e) {
            $arrResponse = ["code" => "400", "error" => $e->getMessage()];
        } catch (\Exception $e) {
            if ($e->getMessage() == 'uk_controllers_unifi') {
                $arrResponse = ["code" => "400", "error" => "Controller já cadastrada!"];
            } else {
                $arrResponse = ["code" => "500", "error" => "Erro ao criar controller: {$e->getMessage()}"];
                $this->logger->addCritical("Error to create Controller: {$e->getMessage()}");
            }
        }
        
        return new JsonResponse($arrResponse);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function unifiValidate(Request $request)
    {
        try {
            $cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

            $v1 = $this->unifiValidateTeste($request, '/api/login', $cookieFile);
            $v2 = $this->unifiValidateTeste($request, '/api/auth/login', $cookieFile);

            $msgError = "Não é possível conectar-se a controller.";
            if (!$v1 && !$v2) {
                return new JsonResponse(["code" => "500", "msg" => "$msgError \n Verifique se a URL e a porta estão corretas ou se o firewall está liberando os IPs e protocolos informados para requisições de entrada e saída "]);
            }
            $v1 = json_decode($v1);
            $v2 = json_decode($v2);
            
            #Validação V1
            if (isset($v1->meta) && $v1->meta->rc === "ok") {
                return $this->unifiV1Validate($request, $cookieFile);
            }
            #validação V2
            if (isset($v2->status)&& $v2->status === "ACTIVE") {
                return $this->unifiV2Validate($request, $cookieFile);
            }
            return new JsonResponse(["code" => "500", "msg" => "Erro ao logar na controller, verifique usuário e senha"]);
        } catch (\Exception $error) {
            return new JsonResponse(["ERROR" => $error->getMessage()]);
        }
    }

    public function unifiValidateTeste(Request $request, $urlPath, $cookieFile)
    {
    $url = $this->buildUrl($request, $urlPath);
    $body = json_encode([
        "username" => $request->get("username"),
        "password" => $request->get("password"),
    ]);
    return $this->sendCurlRequest($url, $body, $cookieFile);
    }

    private function sendCurlRequest($url, $body, $cookieFile)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: */*'
            ],
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function buildUrl(Request $request, $path)
    {
        $address = rtrim($request->get("address"), "/");
        $port = $request->get("port");
        return "$address:$port$path";
    }

    public function unifiV1Validate(Request $request, $cookieFile)
    {
        try {
            $url = $this->buildUrl($request, '/api/self');

            $username = $request->get("username");
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: */*'
            ],
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => false, 
            ]);
            $res = curl_exec($curl);
            curl_close($curl);

            $msgError = "Não é possível consultar os dados do usuário!";
            if (!$res) {
                return new JsonResponse(["code" => "500", "msg" => "$msgError\nVerifique se a url e porta estão corretos."]);
            }

            $res = json_decode($res);            
            if ($res->meta->rc === "error") {
                return new JsonResponse(["code" => "500", "msg" => "Erro ao buscar dados do usuário informado."]);
            }
            if ($res->meta->rc === "ok") {
                if ($res->data[0]->is_super) {
                    return new JsonResponse(["code" => "200", "msg" => "Controller validada com sucesso!"]);
                }
                return new JsonResponse(["code" => "500", "msg" => "Usuário [$username] não possui privilégios de super usuário!"]);
            }
        
        } catch (\Exception $error) {
            return new JsonResponse(["ERROR" => $error->getMessage()]);
        }}


        public function unifiV2Validate(Request $request, $cookieFile)
    {
        try {
            $url = $this->buildUrl($request, '/api/users/self');
            $username = $request->get("username");
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: */*'
            ],
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => false, 
            ]);
            $res = curl_exec($curl);
            curl_close($curl);

            $msgError = "Não é possível consultar os dados do usuário!";
            if (!$res) {
                return new JsonResponse(["code" => "500", "msg" => "$msgError\nVerifique se a url e porta estão corretos."]);
            }

            $res = json_decode($res);
            if ($res->status === "ACTIVE") {
                if (isset($res->permissions) && $res->permissions->{"network.management"}[0] == "admin") {
                    return new JsonResponse(["code" => "200", "msg" => "Controller validada com sucesso!"]);
                }
                return new JsonResponse(["code" => "500", "msg" => "Usuário [$username] não possui privilégios de super usuário!"]);
            }
        
        } catch (\Exception $error) {
            return new JsonResponse(["ERROR" => $error->getMessage()]);
        }}

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
