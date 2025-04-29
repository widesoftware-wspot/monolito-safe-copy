<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\AdminBundle\Form\BlacklistType;
use Wideti\AdminBundle\Form\BlacklistFilterType;
use Wideti\DomainBundle\Entity\Blacklist;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Blacklist\BlacklistServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class BlacklistController
{
    use BlacklistServiceAware;
    use TwigAware;
    use FlashMessageAware;
    use SecurityAware;
    use ModuleAware;
    use MongoAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * BlacklistController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param AnalyticsService $analyticsService
     */
	public function __construct(AdminControllerHelper $controllerHelper, AnalyticsService $analyticsService)
    {
        $this->controllerHelper = $controllerHelper;
        $this->analyticsService = $analyticsService;
    }

	/**
	 * @param $page
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
	public function indexAction($page, Request $request)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $formFilter = $this->controllerHelper->createForm(
            BlacklistFilterType::class,
            null,
            [ 'action' => $this->controllerHelper->generateUrl('blacklist_list') ]
        );

        $formFilter->handleRequest($request);
        $pagination = $this->blacklistService->paginatedSearch($page, $formFilter->getData(), 10, $this->getLoggedClient()->getId());

        return $this->render('@Admin/Blacklist/index.html.twig', [
            'pagination' => $pagination,
            'form' => $formFilter->createView(),
            'btnCancel' => true,
            'client'    =>$client
        ]);
    }

    public function showAction(Blacklist $blacklist)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $guests = $this->blacklistService->getAffectedGuestsBy($client, $blacklist->getMacAddress());

        return $this->render('@Admin/Blacklist/show.html.twig', [
            'guests'    => $guests,
            'blacklist' => $blacklist
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
	public function createAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $blacklist = new Blacklist();

        $formFilter = $this->controllerHelper->createForm(BlacklistType::class, $blacklist);
        $formFilter->handleRequest($request);

        if ($formFilter->isValid()) {
            $blacklist = $formFilter->getData();
            $client = $this->getLoggedClient();

            try {
                $this->blacklistService->create($blacklist, $client->getId());
                $this->setCreatedFlashMessage();
                $this->analyticsService->handler($request, true);
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('blacklist_list', []));
            } catch (UniqueFieldException $e) {
                $formFilter->get('macAddress')
                    ->addError(new FormError('Este mac address já esta bloqueado'));
            }
        }

        return $this->render('@Admin/Blacklist/new.html.twig', [
            'form' => $formFilter->createView()
        ]);
    }

    public function editAction(Blacklist $blacklist, Request $request)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $oldMacValue = $blacklist->getMacAddress();
        $formFilter = $this->controllerHelper->createForm(BlacklistType::class, $blacklist);
        $formFilter->handleRequest($request);

        if ($formFilter->isValid()) {
            $blacklist = $formFilter->getData();

            try {
                $this->blacklistService->update($blacklist, $client->getId(), $oldMacValue);
                $this->setUpdatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('blacklist_list', []));
            } catch (UniqueFieldException $e) {
                $formFilter->get('macAddress')
                    ->addError(new FormError('Este mac address já esta bloqueado'));
            }
        }

        return $this->render('@Admin/Blacklist/edit.html.twig', [
            'form' => $formFilter->createView(),
            'blacklist' => $blacklist
        ]);
    }

    public function deleteAction(Blacklist $blacklist, Request $request)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->blacklistService->delete($blacklist);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Bloqueio removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Exclusão não permitida'
                ]
            );
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('blacklist_list'));
    }

    public function blockGuestByMacAction($mac, Request $request)
    {
        $client = $this->getLoggedClient();

        if (!$this->moduleService->modulePermission('blacklist')) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Modulo não esta ativo para este cliente.'
                ]
            );
        }

        try {
            $this->blacklistService->blockByMacAddress($mac, $client->getId());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Dispositivo bloqueado com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Não foi possível bloquear o dispositivo, tente novamente mais tarde '
                ]
            );
        }
    }

    public function unblockGuestByMacAction($mac, Request $request)
    {
        if (!$this->moduleService->modulePermission('blacklist')) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Modulo não esta ativo para este cliente.'
                ]
            );
        }

        try {
            $client = $this->getLoggedClient();
            $this->blacklistService->unblockByMacAddress($mac, $client->getId());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Dispositivo desbloqueado com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Não foi possível desbloquear o dispositivo, tente novamente mais tarde'
                ]
            );
        }
    }
}
