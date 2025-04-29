<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\AdminBundle\Form\BusinessHoursType;
use Wideti\DomainBundle\Asserts\EntitiesExistsAssert;
use Wideti\DomainBundle\Entity\BusinessHours;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\EmptyBussiness;
use Wideti\DomainBundle\Exception\EmptyBussinessHoursExeception;
use Wideti\DomainBundle\Exception\EmptyEntityException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursServiceAware;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class BusinessHoursController
{
    use EntityManagerAware;
    use BusinessHoursServiceAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use GroupServiceAware;

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
     * BusinessHoursController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        ConfigurationService $configurationService,
        AdminControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        AnalyticsService $analyticsService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->cacheService         = $cacheService;
        $this->analyticsService     = $analyticsService;
        $this->days = [
            'monday' => 'Segunda-feira',
            'tuesday'=> 'Terça-feira',
            'wednesday'=> 'Quarta-feira',
            'thursday'=> 'Quinta-feira',
            'friday'=> 'Sexta-feira',
            'saturday'=> 'Sábado',
            'sunday'=> 'Domingo'
        ];
        
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
	public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('business_hours')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $this->session->remove('businessHoursId');

        $moduleStatus = $this->moduleService->checkModuleIsActive('business_hours');

        $entities = $this->em
            ->getRepository('DomainBundle:BusinessHours')
            ->getAll($client);

        try {
            EntitiesExistsAssert::exists($entities);
        } catch (EmptyEntityException $e) {
            return $this->render(
                'AdminBundle:BusinessHours:index.html.twig',
                [
                    'entities'      => $entities,
                    'moduleStatus'  => $moduleStatus,
                    'block'         => $request->get('block'),
                    'enableActive'  => false
                ]
            );
        }

        return $this->render(
            'AdminBundle:BusinessHours:index.html.twig',
            [
	            'entities'      => $entities,
	            'moduleStatus'  => $moduleStatus,
	            'block'         => $request->get('block'),
                'enableActive'  => true
            ]
        );
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
	public function createAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('business_hours')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $client = $this->getLoggedClient();

        $businessHours = new BusinessHours();

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = null;

        $form = $this->controllerHelper->createForm(
            BusinessHoursType::class,
            null,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->businessHoursService->create($businessHours, $form->getData());
            $this->setCreatedFlashMessage();
            $this->analyticsService->handler($request, true);
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('business_hours'));
        }

        return $this->render(
            'AdminBundle:BusinessHours:form.html.twig',
            array(
                'entity' => $businessHours,
                'form'   => $form->createView(),
                'days'   => $this->days
            )
        );
    }

    public function editAction(BusinessHours $businessHours, Request $request)
    {
        $this->session->set('businessHoursId', $businessHours->getId());

        if (!$this->moduleService->modulePermission('business_hours')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $client = $this->getLoggedClient();

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = $businessHours->getId();

        $form = $this->controllerHelper->createForm(
            BusinessHoursType::class,
            null,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->businessHoursService->update($businessHours, $form->getData());
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('business_hours'));
        }

        return $this->render(
            'AdminBundle:BusinessHours:form.html.twig',
            array(
                'entity' => $businessHours,
                'form'   => $form->createView(),
                'days'   => $this->days
            )
        );
    }

    /**
     * @param BusinessHours $businessHours
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function deleteAction(BusinessHours $businessHours, Request $request)
    {
        if (!$this->moduleService->modulePermission('business_hours')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->businessHoursService->delete($businessHours);

            $entities = $this->em->getRepository('DomainBundle:BusinessHours')->getAll($client);

            if (!$entities) {
                $module = $this->em
                    ->getRepository('DomainBundle:ModuleConfigurationValue')
                    ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_business_hours');

                $this->moduleService->enableOrDisableModule($module, false);
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'msg',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'msg',
                    'message' => 'Exclusão não permitida'
                ]
            );
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('business_hours_list'));
    }

    public function moduleConfigAction(Request $request)
    {
        $nas                            = $this->session->get(Nas::NAS_SESSION_KEY);
        $client                         = $this->getLoggedClient();
        $status                         = $request->get('status');
        $confirmation                   = $this->configurationService->get($nas, $client, 'confirmation_email');
        $blockPerTimeOrAccessValidity   = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');
        $accessCode                     = $this->em
            ->getRepository("DomainBundle:ModuleConfigurationValue")
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_access_code');

        if ($status == 'enable' &&
            ($confirmation == 1 || $blockPerTimeOrAccessValidity || $accessCode->getValue() == 1)
        ) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('business_hours', [
                'block' => true
            ]));
        }

        $businessHours = $this->em
            ->getRepository('DomainBundle:ModuleConfigurationValue')
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_business_hours');

        $this->moduleService->enableOrDisableModule($businessHours, $status);

        if ($status == 'disable') {
            $this->configurationService->deleteExpiration($this->getLoggedClient());
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('business_hours'));
    }
}
