<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\AdminBundle\Form\AccessPointsGroupsType;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\Pagination;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\AccessPointsGroups\GroupHierarchyCountService;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Doctrine\ORM\EntityManager;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;


/**
 * Class AccessPointsGroupsController
 * @package Wideti\AdminBundle\Controller
 */
class AccessPointsGroupsController
{
    use EntityManagerAware;
    use TwigAware;
    use FlashMessageAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var GroupHierarchyCountService
     */
    private $groupHierarchyCountService;
    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * AccessPointsGroupsController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param ClientService $clientService
     * @param GroupHierarchyCountService $groupHierarchyCountService
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param AnalyticsService $analyticsService
     */
    public function __construct
    (
        AdminControllerHelper $controllerHelper,
        ClientService $clientService,
        GroupHierarchyCountService $groupHierarchyCountService,
        AccessPointsGroupsService $accessPointsGroupsService,
        AnalyticsService $analyticsService
    )
    {
        $this->controllerHelper           = $controllerHelper;
        $this->clientService              = $clientService;
        $this->groupHierarchyCountService = $groupHierarchyCountService;
        $this->accessPointsGroupsService  = $accessPointsGroupsService;
        $this->analyticsService = $analyticsService;
    }

	/**
     * @param $page
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function indexAction($page)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $count = $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from('DomainBundle:AccessPointsGroups', 'a')
            ->where('a.client = :client')
            ->setParameter('client', $this->getLoggedClient()->getId())
            ->getQuery()
            ->getSingleScalarResult();

        $pagination = new Pagination($page, $count, 20);
        $pagination_array = $pagination->createPagination();

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);
        return $this->render("AdminBundle:AccessPointsGroups:index.html.twig", [
            "pagination" => $pagination_array,
            "jsonGroup" => $this->getJsonGroups($pagination, $pagination_array),
        ]);
    }

	/**
	 * @ParamConverter(
	 *      "group",
	 *      class       = "DomainBundle:AccessPointsGroups",
	 *      converter   = "client",
	 *      options     = {"message" = "Ponto de acesso não encontrado."}
	 * )
	 * @param AccessPointsGroups $group
     * @param $page
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function showAction(AccessPointsGroups $group, $page)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $count = $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from('DomainBundle:AccessPoints', 'a')
            ->where('a.client = :client')
            ->andWhere('a.group = :group')
            ->setParameter('client', $this->getLoggedClient()->getId())
            ->setParameter('group', $group->getId())
            ->getQuery()
            ->getSingleScalarResult();

        $pagination       = new Pagination($page, $count);
        $pagination_array = $pagination->createPagination();

        $accessPoints = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findBy(['group' => $group->getId()],
                null,
                $pagination->getPerPage(),
                $pagination_array['offset']
            );

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        return $this->render(
            'AdminBundle:AccessPointsGroups:show.html.twig',
            [   'accessPoints'           => $accessPoints,
                'pagination'             => $pagination_array,
                'entity'                 => $group,
                'groupsUnderItsHierarch' => $this->groupHierarchyCountService->count($group->getId())
            ]
        );
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function newAction(Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entity = new AccessPointsGroups();

        $options['attr']['client'] = $this->getLoggedClient()->getId();

        $form   = $this->controllerHelper->createForm(AccessPointsGroupsType::class, $entity, $options);
        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($entity->getAccessPoints() as $ap) {
                $ap->setGroup($entity);
            }

            $client = $this->clientService->refreshEntity($this->getLoggedClient());
            $entity->setClient($client);
            try {
                $this->accessPointsGroupsService->create($entity);
                $this->setCreatedFlashMessage();
            } catch (\Exception $e) {

                $this->setFailToCreateFlashMessage();
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl(
                        'configurations_edit',
                        [
                            'groupId' => $entity->getId()
                        ]
                    )
                );
            }

            $this->analyticsService->handler($request, true);

            if ($entity->getParentConfigurations()) {
                return $this->controllerHelper->redirectToRoute("access_points_groups");
            }

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    'configurations_edit',
                    [
                        'groupId' => $entity->getId()
                    ]
                )
            );
        }

        return $this->render(
            'AdminBundle:AccessPointsGroups:form.html.twig',
            [
                'entity' => $entity,
                'form'   => $form->createView()
            ]
        );
    }

    /**
     * @ParamConverter(
     *      "group",
     *      class       = "DomainBundle:AccessPointsGroups",
     *      converter   = "client",
     *      options     = {"message" = "Ponto de acesso não encontrado."}
     * )
     * @param Request $request
     * @param AccessPointsGroups $group
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editAction(Request $request, AccessPointsGroups $group)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $options['attr']['client'] = $this->getLoggedClient()->getId();
        $options['attr']['id'] = $group->getId();

	    $form = $this->controllerHelper->createForm(AccessPointsGroupsType::class, $group, $options);
	    $form->handleRequest($request);

	    if ($form->isValid()) {
	        foreach ($group->getAccessPoints() as $ap) {
		        $ap->setGroup($group);
	        }

            $this->accessPointsGroupsService->update($group, $form->getData());
            $this->setUpdatedFlashMessage();

            $this->analyticsService->handler($request, true);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_points_groups'));
        }

        return $this->render(
            'AdminBundle:AccessPointsGroups:form.html.twig',
            [
                'entity' => $group,
                'form'   => $form->createView()
            ]
        );
    }

	/**
	 * @ParamConverter(
	 *      "group",
	 *      class       = "DomainBundle:AccessPointsGroups",
	 *      converter   = "client",
	 *      options     = {"message" = "Ponto de acesso não encontrado."}
	 * )
	 * @param Request $request
	 * @param AccessPointsGroups $group
	 * @return JsonResponse
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function deleteAction(Request $request, AccessPointsGroups $group)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->accessPointsGroupsService->delete($group);
            $this->setDeletedFlashMessage();
            return new JsonResponse([
                'type'    => 'success',
                'data' => '{"message": "", "data": "{}"}'
            ]);
        } catch(ForeignKeyConstraintViolationException $e) {
            return new JsonResponse([
                'type'    => 'error',
                'data' => '{"message": "Não é possível excluir um grupo com Ponto de acesso vinculado.", "data": "{}"}'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'type'    => 'error',
                'data' => '{"message": "'.$e->getMessage().'", "data": "{}"}'
            ]);
        }
    }

    /**
     * @return JsonResponse
     */
    public function changeParentAction(Request $request)
    {
        $id = (int)$request->get("id");
        $parentId = (int)$request->get("parent_id");

        try {
            $this->accessPointsGroupsService->changeParent($id, $parentId);
        } catch (\Exception $e) {
            return new JsonResponse([
                'type'    => 'error',
                'data' => '{"message": "'.$e->getMessage().'", "data": "{}"}'
            ], 500);
        }

        return new JsonResponse([
            'type' => 'success',
            'data' => '{"message": "Movido com sucesso", "data": "{}"}',
            'json' => $this->getJsonGroups()
        ]);
    }

    /**
     * @return string
     */
    public function getJsonGroups($pagination, $pagination_array)
    {
        return $this->accessPointsGroupsService->getJsonGroupView(
            $this->accessPointsGroupsService->getEntities($this->getLoggedClient(),$pagination, $pagination_array )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function setIsMasterAction(Request $request)
    {
        $id = (int) $request->get("id");

        try {
            $this->accessPointsGroupsService->setIsMaster($id);
        } catch (\Exception $e) {
            return new JsonResponse([
                'type'    => 'error',
                'data' => '{"message": "' . $e->getMessage() . '", "data": "{}"}'
            ], 500);
        }

        return new JsonResponse([
            'type' => 'success',
            'data' => '{"message": "Alterado para grupo Master com sucesso", "data": "{}"}',
            'json' => $this->getJsonGroups()
        ]);
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
