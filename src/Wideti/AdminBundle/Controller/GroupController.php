<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\AdminBundle\Form\GroupType;
use Wideti\AdminBundle\Form\GroupFilterType;
use Wideti\AdminBundle\Form\GuestGroupFindType;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class GroupController
{
    use GroupServiceAware;
    use TwigAware;
    use FlashMessageAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ModuleService
     */
    private $moduleService;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var AccessPointsService
     */
    private $accessPointsService;

    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;

    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
	/**
	 * @var AnalyticsService
	 */
	private $analyticsService;
    /**
     * @var Auditor
     */
	private $auditor;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var auditLogService
     */
    private $auditLogService;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * GroupController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param ModuleService $moduleService
     * @param EntityManager $em
     * @param CacheServiceImp $cacheService
     * @param Container $container
     * @param AccessPointsService $accessPointsService
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param CustomFieldsService $customFieldsService
     * @param AnalyticsService $analyticsService
     * @param Auditor $auditor
     */
    public function __construct(
        ConfigurationService $configurationService,
        AdminControllerHelper $controllerHelper,
        ModuleService $moduleService,
        EntityManager $em,
        CacheServiceImp $cacheService,
        Container $container,
        AccessPointsService $accessPointsService,
        AccessPointsGroupsService $accessPointsGroupsService,
        CustomFieldsService $customFieldsService,
		AnalyticsService $analyticsService,
        Auditor $auditor,
        LegalBaseManagerService $legalBaseManagerService,
        AuditLogService $auditLogService
    ) {
        $this->configurationService      = $configurationService;
        $this->controllerHelper          = $controllerHelper;
        $this->moduleService             = $moduleService;
        $this->em                        = $em;
        $this->cacheService              = $cacheService;
        $this->container                 = $container;
        $this->accessPointsService       = $accessPointsService;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->customFieldsService       = $customFieldsService;
	    $this->analyticsService          = $analyticsService;
	    $this->auditor                   = $auditor;
        $this->legalBaseManager          = $legalBaseManagerService;
        $this->auditLogService           = $auditLogService;
    }

	/**
	 * @param Request $request
	 * @return RedirectResponse|Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function createAction(Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $group = new Group();
        $defaultConfigurations = $this->groupService->getAllDefaultConfigurations();

        $form = $this->controllerHelper->createForm(GroupType::class, [
            'entity' => $group,
            'configurations' => $defaultConfigurations
        ]);

        $form->handleRequest($request);

        $usedApIds = $this->groupService->getAllIdsFromApsInGuestGroups();
        $usedGroupIds = $this->groupService->getAllIdsFromGroupInGuestGroups();
        $apIdsThatAreBeingUsed = json_encode($usedApIds);
        $groupsThatAreBeingUsed = json_encode($usedGroupIds);


        if ($form->isValid()) {
            $data = $form->getData();

            $apsAndGroups = $request->get('wideti_AdminBundle_guest_group');
            $apsIds = [];
            $apGroupsIds = [];
            foreach ($apsAndGroups as $apAndGroup) {
                $apAndGroup = json_decode($apAndGroup, true);
                foreach ($apAndGroup as $item) {
                    if ($item['type'] === 'ap') {
                        array_push($apsIds, $item['id']);
                    }
                    if ($item['type'] === 'group') {
                        array_push($apGroupsIds, $item['id']);
                    }
                }
            }

	        if (($data['enable_block_per_time'] || $data['enable_validity_access']) && $this->checkModulesActive()) {
                return $this->render(
                    '@Admin/Group/new.html.twig',
                    [
                        'form'                  => $form->createView(),
                        'defaultConfigurations' => $defaultConfigurations,
                        'blockEnable'           => true,
	                    'apIdsThatAreBeingUsed' => $apIdsThatAreBeingUsed,
	                    'groupsThatAreBeingUsed' => $groupsThatAreBeingUsed
                    ]
                );
            }

            $groupToSave = $this->groupService->prepareGroupToSave($data, $apsIds, $apGroupsIds);
            $this->groupService->create($groupToSave);
            $this->analyticsService->handler($request, true);


            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('group_list'));
        }

        return $this->render(
            '@Admin/Group/new.html.twig',
            [
                'form'                  => $form->createView(),
                'defaultConfigurations' => $defaultConfigurations,
                'blockEnable'           => $this->checkModulesActive(),
                'apIdsThatAreBeingUsed' => $apIdsThatAreBeingUsed,
                'groupsThatAreBeingUsed' => $groupsThatAreBeingUsed
            ]
        );
    }

    /**
     * @return bool
     */
    public function checkModulesActive()
    {
        $nas                            = $this->session->get(Nas::NAS_SESSION_KEY);
        $client                         = $this->getLoggedClient();

        // $confirmation                   = $this->configurationService->get($nas, $client, 'confirmation_email');
        $accessCode                     = $this->em
            ->getRepository("DomainBundle:ModuleConfigurationValue")
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_access_code');
        $businessHours                  = $this->em
            ->getRepository("DomainBundle:ModuleConfigurationValue")
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_business_hours');


        if ($accessCode->getValue() == 1 || $businessHours->getValue() == 1) {
            return true;
        }

        return false;
    }

    /**
     * @param Group $group
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function editAction(Group $group, Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $defaultConfigurations = $this->groupService->getAllDefaultConfigurations();

        $form = $this->controllerHelper->createForm( GroupType::class, $group, [
            'data' => [
                'entity' => $group,
                'configurations' => $group->getConfigurations()
            ]
        ]);

        $form->handleRequest($request);

        $usedApIds = $this->groupService->getAllIdsFromApsInGuestGroups();
        $usedGroupIds = $this->groupService->getAllIdsFromGroupInGuestGroups();
        $apIdsThatAreBeingUsed = json_encode($usedApIds);
        $groupsThatAreBeingUsed = json_encode($usedGroupIds);
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if ($form->isValid()) {
            $before         = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');
            $data           = $form->getData();

            $apsAndGroups = $request->get('wideti_AdminBundle_guest_group');
            $apsIds = [];
            $apGroupsIds = [];
            foreach ($apsAndGroups as $apAndGroup) {
                $apAndGroup = json_decode($apAndGroup, true);
                foreach ($apAndGroup as $item) {
                    if ($item['type'] === 'ap') {
                        array_push($apsIds, $item['id']);
                    }
                    if ($item['type'] === 'group') {
                        array_push($apGroupsIds, $item['id']);
                    }
                }
            }

            if (($data['enable_block_per_time'] || $data['enable_validity_access']) && $this->checkModulesActive()) {
                return $this->render(
                    '@Admin/Group/edit.html.twig',
                    [
                        'user'                  => $user,
                        'form'                  => $form->createView(),
                        'defaultConfigurations' => $defaultConfigurations,
                        'group'                 => $group,
                        'blockEnable'           => true,
	                    'apIdsThatAreBeingUsed' => $apIdsThatAreBeingUsed,
	                    'groupsThatAreBeingUsed' => $groupsThatAreBeingUsed
                    ]
                );
            }
 
            $this->analyticsService->handler($request, $data);

            $groupToUpdate  = $this->groupService->prepareGroupToSave($data, $apsIds, $apGroupsIds);
            $this->groupService->create($groupToUpdate);
            $after          = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');

            if ($before || $after) {
                $this->configurationService->deleteExpirationByGuestGroup($this->getLoggedClient()->getId(), $group->getId());
            }


            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('group_list'));
        }

        return $this->render(
            '@Admin/Group/edit.html.twig',
            [
                'user'                   => $user,
                'form'                   => $form -> createView(),
                'defaultConfigurations'  => $defaultConfigurations,
                'group'                  => $group,
                'blockEnable'            => $this->checkModulesActive(),
                'apIdsThatAreBeingUsed'  => $apIdsThatAreBeingUsed,
                'groupsThatAreBeingUsed' => $groupsThatAreBeingUsed
            ]
        );
    }

	/**
	 * @param Request $request
	 * @return Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function listAction(Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
        throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $formFilter = $this->controllerHelper->createForm(
            GroupFilterType::class,
            null,
            [
                'action' => $this->controllerHelper->generateUrl('group_list')
            ]
        );

        $formFilter->handleRequest($request);
        $groups = $this->groupService->getAllGroups($formFilter->getData());

        return $this->render(
            '@Admin/Group/index.html.twig',
            [
                "groups" => $groups,
                'form' => $formFilter->createView(),
                'btnCancel' => false
            ]
        );
    }

    /**
     * @param Group $group
     * @return JsonResponse
     */
    public function deleteAction(Group $group)
    {
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        $before = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');
        $result = $this->groupService->remove($group, $client);
        $after  = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');

        if ($before || $after) {
            $this->configurationService->deleteExpirationByGuestGroup($this->getLoggedClient()->getId(), $group->getId());
        }

        if ($result) {
            $message = [
                'status' => "success",
                'message' => 'Grupo removido com sucesso, estamos movendo os visitantes para o Grupo Visitantes'
            ];

            return new JsonResponse($message);
        }

        $message = [
            "status" => 'error',
            'message' => 'Não é possível excluir o grupo selecionado.'
        ];

        return new JsonResponse($message);
    }

    /**
     * @param Group $group
     * @param Request $request
     * @return Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function showAction(Group $group, Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $groupId = $request->get('id');
        $formFilter = $this->controllerHelper->createForm(
            GuestGroupFindType::class,
            null,
            [
                'action' => $this->controllerHelper->generateUrl('group_show', ['id' => $groupId])
            ]
        );

        $legalKindKey = $this->legalBaseManager->getActiveLegalBase($client)->getLegalKind()->getKey();
        $formFilter->handleRequest($request);
        $page = $request->get('page');
        if ($formFilter->isValid()) {
            $filterField = str_replace('properties.', '',$formFilter->getData()['filter']);
            $filterValue = "";
            if ($filterField != "all") {
                $filterValue = $formFilter->getData()['value_' . $filterField ];
            }
            $guests = $this->groupService->getGuestsByGroupPaginated($group, $page, $legalKindKey, 20, $filterField, $filterValue);
        } else {
            $guests = $this->groupService->getGuestsByGroupPaginated($group, $page, $legalKindKey);
        }

        $allGroups = $this->groupService->getAllGroups();

        $accessPointsArray = [];
        $accessPoints = $group->getAccessPoint();
        foreach ($accessPoints as $accessPoint) {
            $apId = $accessPoint->getMysqlId();
            $ap = $this->accessPointsService->findById($apId);
            array_push($accessPointsArray, $ap);
        }

        $accessPointsGroups = $group->getAccessPointGroup();

        foreach ($accessPointsGroups as $accessPointsGroup) {
            $groupId = $accessPointsGroup->getMysqlId();
            $apGroup = $this->accessPointsGroupsService->getGroupById($groupId);
            $aps = $apGroup->getAccessPoints();

            foreach ($aps as $ap) {
                array_push($accessPointsArray, $ap);
            }
        }

        $loginField = $this->customFieldsService->getLoginField();
        $fields = $this->customFieldsService->getCustomFields();
        $listField = $this->createListFields($loginField, $fields);

        /**
         * @var $g Guest
         */
        $user = $this->controllerHelper->getUser();
        foreach ($guests as $g) {
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $g->getMysql())
                ->withType(Events::view())
                ->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante na tela de regras de acesso')
                ->addDescription(AuditEvent::EN_US, 'User viewed visitor on the access rules screen')
                ->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la pantalla de reglas de acceso');
            $this->auditor->push($event);
        }

        return $this->render(
            '@Admin/Group/show.html.twig',
            [
                'customFields' => $listField,
                'loginField' => $loginField[0]->getIdentifier(),
                'group' => $group,
                'guests' => $guests,
                'form' => $formFilter->createView(),
                'allGroups' => $allGroups,
                'accessPointsArray' => $accessPointsArray
            ]
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function transferGuestAction(Request $request)
    {
        $groupShowId    = $request->get('group-show-id');
        $groupShortcode = $request->get('group');

        $guestsIds = $request->get('guest-id');
        $this->groupService->sendGuestTo($groupShortcode, $guestsIds);

        $this->setFlashMessage('notice', 'Visitantes transferidos com sucesso');
        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('group_show', ['id' => $groupShowId])
        );
    }

    public function createListFields($loginField, $fields)
    {
        $customFields = array();
        $listFields = [
            "email", "name"
        ];

        foreach ($fields as $field) {
            if ($loginField[0] == $field || in_array($field->getIdentifier(), $listFields)) {
                $customFields[$field->getIdentifier()] = $field->getName()['pt_br'];
            }
        }
        return $customFields;
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
