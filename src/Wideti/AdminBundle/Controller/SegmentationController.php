<?php

namespace Wideti\AdminBundle\Controller;

use Exception;
use Monolog\Logger;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\AdminBundle\Form\SegmentationType;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Exception\ClientPlanNotFoundException;
use Wideti\DomainBundle\Exception\NotAuthorizedPlanException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\SegmentationHelper;
use Wideti\DomainBundle\Repository\SegmentationRepository;
use Wideti\DomainBundle\Service\ApiWSpot\ApiWSpotService;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\Segmentation\CreateSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\DeleteSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;
use Wideti\DomainBundle\Service\Segmentation\EditSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\ExportSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;
use Wideti\DomainBundle\Service\Segmentation\Resolver\FilterResolver;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class SegmentationController
{
    use SecurityAware;
    use TwigAware;
    use FlashMessageAware;
    use PaginatorAware;
    use EntityManagerAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ModuleService
     */
    private $moduleService;
    /**
     * @var SegmentationRepository
     */
    private $repository;
    /**
     * @var ApiWSpotService
     */
    private $apiWSpotService;
    /**
     * @var CreateSegmentationService
     */
    private $createSegmentationService;
    /**
     * @var EditSegmentationService
     */
    private $editSegmentationService;
    /**
     * @var DeleteSegmentationService
     */
    private $deleteSegmentationService;
    /**
     * @var ExportSegmentationService
     */
    private $exportSegmentationService;
    /**
     * @var FilterResolver
     */
    private $filterResolver;
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * SegmentationController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param ModuleService $moduleService
     * @param SegmentationRepository $repository
     * @param ApiWSpotService $apiWSpotService
     * @param CreateSegmentationService $createSegmentationService
     * @param EditSegmentationService $editSegmentationService
     * @param DeleteSegmentationService $deleteSegmentationService
     * @param ExportSegmentationService $exportSegmentationService
     * @param FilterResolver $filterResolver
     * @param GuestRepository $guestRepository
     * @param CustomFieldsService $customFieldsService
     * @param Logger $logger
     * @param Auditor $auditor
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        ModuleService $moduleService,
        SegmentationRepository $repository,
        ApiWSpotService $apiWSpotService,
        CreateSegmentationService $createSegmentationService,
        EditSegmentationService $editSegmentationService,
        DeleteSegmentationService $deleteSegmentationService,
        ExportSegmentationService $exportSegmentationService,
        FilterResolver $filterResolver,
        GuestRepository $guestRepository,
        CustomFieldsService $customFieldsService,
        Logger $logger,
        Auditor $auditor
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->moduleService = $moduleService;
        $this->repository = $repository;
        $this->apiWSpotService = $apiWSpotService;
        $this->createSegmentationService = $createSegmentationService;
        $this->editSegmentationService = $editSegmentationService;
        $this->deleteSegmentationService = $deleteSegmentationService;
        $this->exportSegmentationService = $exportSegmentationService;
        $this->filterResolver = $filterResolver;
        $this->guestRepository = $guestRepository;
        $this->customFieldsService = $customFieldsService;
        $this->logger = $logger;
        $this->auditor = $auditor;
    }

	/**
	 * @return Response
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 */
    public function indexAction()
    {
        if (!$this->moduleService->modulePermission('segmentation')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entities = $this->repository->findBy([
        	'client' => $client->getId()
        ]);

        return $this->render(
            'AdminBundle:Segmentation:index.html.twig',
            [
                'entities' => $entities
            ]
        );
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 */
    public function newAction(Request $request)
    {
        $client = $this->getLoggedClient();

        /**
         * @var ApiWSpot $token
         */
        $token = $this->apiWSpotService->getTokenByResourceName($client, 'segmentation');

        if (!$this->moduleService->modulePermission('segmentation') || !$token) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        PlanAssert::checkOrThrow($client, Plan::PRO);

        $form = $this->controllerHelper->createForm(SegmentationType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $formData = $form->getData();

            $segmentation = new Segmentation();
            $segmentation->setClient($client->getId());
            $segmentation->setTitle($formData['title']);
            $segmentation->setFilter(json_encode([
                [
                    'type' => Filter::TYPE_ALL,
                    'default' => [
                        $formData['filter'] => [
                            'identifier' => $formData['filter'],
                            'equality' => strtoupper(FilterItem::RANGE),
                            'type' => FilterItem::TYPE_DATE,
                            'value' => date_format($formData['startDate'], 'Y-m-d') . '|' . date_format($formData['endDate'], 'Y-m-d')
                        ]
                    ]
                ]
            ]));

            try {
                $this->createSegmentationService->create($segmentation);
                $this->setCreatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('segmentation'));
            } catch (Exception $e) {
                $form->addError(new FormError('Ocorreu um erro ao criar a segmentação.'));
                $this->logger->addCritical('[Segmentation] Fail to create segmentation', [ 'error' => $e->getTraceAsString() ]);
            }
        }

        return $this->render(
            'AdminBundle:Segmentation:form.html.twig',
            [
                'clientId'   => $this->getLoggedClient()->getId(),
                'user'       => $this->controllerHelper->getUser(),
                'token'      => $token->getToken(),
                'form'       => $form->createView()
            ]
        );
    }

    /**
     * @param Segmentation $segmentation
     * @return Response
     * @throws Exception
     */
    public function showAction($page, Segmentation $segmentation)
    {
        if (!$this->moduleService->modulePermission('segmentation')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $segmentation   = $this->repository->find($segmentation->getId());
        $filterDto      = SegmentationHelper::convertToFilterDto($this->getLoggedClient()->getId(), $segmentation);
        $guests         = $this->filterResolver->resolve($filterDto);
        if ($guests instanceof Filter) {
            $guests = [];
        }
        $pagination     = $this->paginator->paginate($guests, $page, 10);
        $loginField     = $this->customFieldsService->getLoginField()[0];

        /**
         * @var $g Guest
         */
        $user = $this->controllerHelper->getUser();
        foreach ($pagination as $g) {
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $g->getMysql())
                ->withType(Events::view())
                ->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante na tela de detalhe de segmentação')
                ->addDescription(AuditEvent::EN_US, 'User viewed visitor on segmentation detail screen')
                ->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la pantalla de detalles de segmentación');
            $this->auditor->push($event);
        }
        return $this->render(
            'AdminBundle:Segmentation:show.html.twig',
            [
                'entity'     => $segmentation,
                'loginField' => $loginField,
                'pagination' => $pagination
            ]
        );
    }

	/**
	 * @param Request $request
	 * @param Segmentation $segmentation
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 */
    public function editAction(Request $request, Segmentation $segmentation)
    {
        $client = $this->getLoggedClient();

        /**
         * @var ApiWSpot $token
         */
        $token = $this->apiWSpotService->getTokenByResourceName($client, 'segmentation');

        if (!$this->moduleService->modulePermission('segmentation')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        PlanAssert::checkOrThrow($client, Plan::PRO);

        $filter = json_decode($segmentation->filter, true)[0];
        $filterItem = $filter['default'][array_keys($filter['default'])[0]];
        $dateRange = explode('|', $filterItem['value']);

        $segmentation->__set('startDate', new \DateTime($dateRange[0]));
        $segmentation->__set('endDate', new \DateTime($dateRange[1]));
        $segmentation->__set('filterValue', $filterItem['identifier']);

        $form   = $this->controllerHelper->createForm(SegmentationType::class, $segmentation);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $formData = $form->getData();

            $segmentation->setStatus(Segmentation::ACTIVE);
            $segmentation->setTitle($formData->getTitle());
            $segmentation->setFilter(json_encode([
                [
                    'type' => Filter::TYPE_ALL,
                    'default' => [
                        $formData->filter => [
                            'identifier' => $formData->filter,
                            'equality' => strtoupper(FilterItem::RANGE),
                            'type' => FilterItem::TYPE_DATE,
                            'value' => $formData->startDate->format('Y-m-d') . '|' . $formData->endDate->format('Y-m-d')
                        ]
                    ]
                ]
            ]));

            try {
                $this->editSegmentationService->edit($segmentation);
                $this->setCreatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('segmentation'));
            } catch (Exception $e) {
                $form->addError(new FormError('Ocorreu um erro ao criar a segmentação.'));
                $this->logger->addCritical('[Segmentation] Fail to create segmentation', [ 'error' => $e->getTraceAsString() ]);
            }
        }

        return $this->render(
            'AdminBundle:Segmentation:form.html.twig',
            [
                'clientId'   => $this->getLoggedClient()->getId(),
                'entity'     => $segmentation,
                'previewUrl' => $this->getPreviewUrl($request, $token),
                'token'      => $token->getToken(),
                'form'       => $form->createView(),
                'user'       => $this->controllerHelper->getUser(),
            ]
        );
    }

	/**
	 * @param Segmentation $segmentation
	 * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function exportAction(Segmentation $segmentation)
    {
        $exportDto = new ExportDto();
        $exportDto->setClient($this->getLoggedClient()->getId());
        $exportDto->setSegmentationId($segmentation->getId());
        $exportDto->setRecipient($this->getUser()->getUsername());

        try {
            $this->exportSegmentationService->requestingExport($exportDto);

            return new JsonResponse(
                [
                    'type'    => 'success',
                    'message' => "A exportação está sendo processada. Em breve o arquivo será enviado para seu e-mail: {$this->getUser()->getUsername()}"
                ]
            );
        } catch (Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation export error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Erro ao solicitar a exportação.'
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('segmentation'));
    }

    public function deleteAction(Request $request, Segmentation $segmentation)
    {
        if (!$this->moduleService->modulePermission('segmentation')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->deleteSegmentationService->delete($segmentation);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Não foi possível excluir o Token.'
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('segmentation'));
    }

    /**
     * @param Request $request
     * @param ApiWSpot $token
     * @return string
     */
    private function getPreviewUrl(Request $request, $token)
    {
        return "https://{$request->getHost()}/api/segmentation/preview?token={$token->getToken()}";
    }
}
