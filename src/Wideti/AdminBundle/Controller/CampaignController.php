<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\AdminBundle\Form\CampaignCallToActionType;
use Wideti\AdminBundle\Form\CampaignFilterType;
use Wideti\AdminBundle\Form\CampaignMediaImageType;
use Wideti\AdminBundle\Form\CampaignMediaVideoType;
use Wideti\DomainBundle\Dto\CampaignDto;
use Wideti\DomainBundle\Entity\CampaignCallToAction;
use Wideti\DomainBundle\Entity\CampaignMediaImage;
use Wideti\DomainBundle\Entity\CampaignMediaVideo;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\VideoSkip;
use Wideti\DomainBundle\Helpers\CampaignMediaHelper;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\CampaignDtoHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Campaign\ActiveCampaignCountService;
use Wideti\DomainBundle\Service\Campaign\CampaignService;
use Wideti\DomainBundle\Service\Campaign\InactiveCampaignCountService;
use Wideti\DomainBundle\Service\CampaignCallToAction\PersistCallToActionService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Media\MediaService;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\SearchAccessPointsAndGroups;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\AdminBundle\Form\CampaignType;
use Wideti\DomainBundle\Helpers\FileUpload;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Class CampaignController
 * @package Wideti\AdminBundle\Controller
 */
class CampaignController
{
    const ON_CREATE = "onCreate";
    const ON_UPDATE = "onUpdate";

    use TwigAware;
    use FlashMessageAware;
    use PaginatorAware;

    private $imageBucket;
    private $videoBucket;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var SearchAccessPointsAndGroups
     */
    private $searchAccessPointsAndGroups;
    /**
     * @var PersistCallToActionService
     */
    private $persistCallToActionService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var CampaignService
     */
    private $campaignService;
    /**
     * @var ModuleService
     */
    private $moduleService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CampaignFilterType
     */
    private $campaignFilterType;
    /**
     * @var ActiveCampaignCountService
     */
    private $activeCampaignCountService;
    /**
     * @var InactiveCampaignCountService
     */
    private $inactiveCampaignCountService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var MediaService
     */
    private $imageService;
    /**
     * @var MediaService
     */
    private $videoService;

    /**
     * CampaignController constructor.
     * @param $imageBucket
     * @param $videoBucket
     * @param AdminControllerHelper $controllerHelper
     * @param ConfigurationService $configurationService
     * @param ValidatorInterface $validator
     * @param FileUpload $fileUpload
     * @param SearchAccessPointsAndGroups $searchAccessPointsAndGroups
     * @param PersistCallToActionService $persistCallToActionService
     * @param EntityManager $entityManager
     * @param CampaignService $campaignService
     * @param ModuleService $moduleService
     * @param Logger $logger
     * @param ActiveCampaignCountService $activeCampaignCountService
     * @param InactiveCampaignCountService $inactiveCampaignCountService
     * @param AnalyticsService $analyticsService
     * @param MediaService $imageService
     * @param MediaService $videoService
     */
    public function __construct(
        $imageBucket,
        $videoBucket,
        AdminControllerHelper $controllerHelper,
        ConfigurationService $configurationService,
        ValidatorInterface $validator,
        FileUpload $fileUpload,
        SearchAccessPointsAndGroups $searchAccessPointsAndGroups,
        PersistCallToActionService $persistCallToActionService,
        EntityManager $entityManager,
        CampaignService $campaignService,
        ModuleService $moduleService,
        Logger $logger,
        ActiveCampaignCountService $activeCampaignCountService,
        InactiveCampaignCountService $inactiveCampaignCountService,
        AnalyticsService $analyticsService,
        MediaService $imageService,
        MediaService $videoService
    ) {
        $this->imageBucket                  = $imageBucket;
        $this->videoBucket                  = $videoBucket;
        $this->controllerHelper             = $controllerHelper;
        $this->configurationService         = $configurationService;
        $this->searchAccessPointsAndGroups  = $searchAccessPointsAndGroups;
        $this->persistCallToActionService   = $persistCallToActionService;
	    $this->entityManager                = $entityManager;
	    $this->campaignService              = $campaignService;
	    $this->moduleService                = $moduleService;
	    $this->logger                       = $logger;
	    $this->activeCampaignCountService   = $activeCampaignCountService;
	    $this->inactiveCampaignCountService = $inactiveCampaignCountService;
	    $this->campaignFilterType           = CampaignFilterType::class;
        $this->analyticsService             = $analyticsService;
        $this->imageService                 = $imageService;
        $this->validator                    = $validator;
        $this->fileUpload                   = $fileUpload;
        $this->videoService                 = $videoService;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('campaign')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $options["attr"]["client"] = $this->getLoggedClient()->getId();
        $filterForm = $this->controllerHelper->createForm($this->campaignFilterType, null, $options);
        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            $filters = $this->campaignService->prepareCampaignFilters($filterForm->getData());
        } else {
            $filters = ["client" => $this->getLoggedClient(), "status" => 1];
        }

        $entities = $this->campaignService->getCampaignByFilter($filters);

        $campaignAccessPoints = [];

        foreach ($entities as $entity) {
            $accessPoints = $this->searchAccessPointsAndGroups->findByCampaignId(
                $entity->getId(),
                $this->getLoggedClient()
            );

            if (count($accessPoints) == 0) {
                $campaignAccessPoints[$entity->getId()][] = "Todos";
            } else {
                foreach ($accessPoints as $accessPoint) {
                    $campaignAccessPoints[$entity->getId()][] = $accessPoint->getName();
                }
            }
        }

        $page = ($request->get("page") < 1) ? 1 : $request->get("page");
        $pagination = $this->paginator->paginate($entities, $page, 25);

        $activeCampaignCount = $this->activeCampaignCountService->quantity(
            $this->getLoggedClient()
        );

        $inactiveCampaignCount = $this->inactiveCampaignCountService->quantity(
            $this->getLoggedClient()
        );

        return $this->render("AdminBundle:Campaign:index.html.twig", [
            "entities"              => $entities,
            "accessPoints"          => $campaignAccessPoints,
            "filterForm"            => $filterForm->createView(),
            "activeCampaignCount"   => $activeCampaignCount,
            "inactiveCampaignCount" => $inactiveCampaignCount,
            "totalCampaignCount"    => ($activeCampaignCount + $inactiveCampaignCount),
            "pagination"            => $pagination
        ]);
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
        if (!$this->moduleService->modulePermission('campaign')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);
        $entity = new Campaign();

        return $this->stepGeneralSettingsAction($request, $entity, self::ON_CREATE);
    }

    /**
     * @param Request $request
     * @param Campaign $campaign
     * @param $action
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stepGeneralSettingsAction(Request $request, Campaign $campaign, $action)
    {
        $client = $this->getLoggedClient();

        $options['attr']['client'] = $client->getId();

        $form = $this->controllerHelper->createForm(
            CampaignType::class,
            $campaign,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($campaign->getCampaignHours() as $campaignHours) {
                $campaignStartTime = $campaignHours->getStartTime();
                $campaignEndTime = $campaignHours->getEndTime();
                $validDate = DateTimeHelper::handleDateTime($campaignStartTime, $campaignEndTime);

                if ($validDate) {
                    $campaign->addCampaignHours($campaignHours);
                } else {
                    $this->setFailToCreateFlashMessage();
                    return $this->controllerHelper->redirect(
                        $this->controllerHelper->generateUrl('campaign')
                    );
                }
            }

            $apsAndGroupsAsEntity = $this->getApsAndGroupsToSave($form);

            $campaign->setAccessPoints($apsAndGroupsAsEntity->getAccessPoints());
            $campaign->setAccessPointsGroups($apsAndGroupsAsEntity->getGroups());
            $campaign->setInAccessPoints((int)$apsAndGroupsAsEntity->isInAccessPointOrGroup());

            if ($action == self::ON_CREATE) {
                $this->campaignService->create($campaign);
                $this->setCreatedFlashMessage();
            } else {
                $this->campaignService->update($campaign);
                $this->setUpdatedFlashMessage();
            }

            $this->analyticsService->handler($request, true);

            if ($form->has('submitAndExit') && $form->get('submitAndExit')->isClicked()) {
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('campaign'));
            }

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('campaign_step_choosing_media', [
                    'id'    => $campaign->getId(),
                    'step'  => 'pre-login',
                    'action' => $action
                ])
            );
        }

        $isWhiteLabel = $client->isWhiteLabel();

        $defaultGroup = $this->configurationService->getByIdentifierOrDefault(null, $client);

        return $this->render(
            'AdminBundle:Campaign/steps:step-1-general.html.twig',
            [
                'entity'    => $campaign,
                'form'      => $form->createView(),
                'config'    => $defaultGroup,
                'isWhiteLabel'   => $isWhiteLabel,
            ]
        );
    }

    public function stepChoosingMediaTypeAction(Request $request, Campaign $campaign)
    {
        $step   = $request->get('step');
        $action = $request->get('action');

        $client = $campaign->getClient();

        $this->session->set(
            "previousPage",
            $this->controllerHelper->generateUrl('campaign_step_choosing_media', [
                'id'        => $campaign->getId(),
                'step'      => $step,
                'action'    => $action
            ])
        );

        return $this->render(
            "AdminBundle:Campaign/steps:step-2-choosing-media-type.html.twig",
            [
                'step'                  => $step,
                'action'                => $action,
                'entity'                => $campaign,
                'hasPreLoginImage'      => CampaignMediaHelper::hasOnPreLogin($campaign)
            ]
        );
    }

    public function stepUploadMediaAction(Request $request, Campaign $campaign)
    {
        $client     = $campaign->getClient();
        $step       = $request->get('step');
        $action     = $request->get('action');
        $mediaStep  = explode('-', $step)[0];

        if ($request->get('mediaType') == 'image') {
            $entity         = new CampaignMediaImage();
            $formType       = CampaignMediaImageType::class;
            $templateView   = 'AdminBundle:Campaign/steps:step-3-media-image.html.twig';
        }

        if ($request->get('mediaType') == 'video') {
            $entity         = new CampaignMediaVideo();
            $formType       = CampaignMediaVideoType::class;
            $templateView   = 'AdminBundle:Campaign/steps:step-3-media-video.html.twig';
            $options['attr']['enableToCta'] = true;

            if ($step == 'pos-login' && $campaign->getCampaignMediaImage()->count() == 0) {
                $options['attr']['enableToCta'] = false;
            }
            $preciseStep = explode("-", $step);
            $videoSkip = $this->getVideoSkip($campaign->getId(), $preciseStep[0]);
        } else {
            $videoSkip = 0;
            $entity->setFullSize(0);
        }

        $entity->setClient($client);
        $entity->setCampaign($campaign);
        $entity->setStep($mediaStep);

        $medias = [
            "imageDesktop"      => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageDesktop"],
            "imageDesktop2"      => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageDesktop2"],
            "imageDesktop3"      => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageDesktop3"],


            "imageMobile"       => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageMobile"],
            "imageMobile2"       => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageMobile2"],
            "imageMobile3"       => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginImageMobile3"],

            "exhibitionTime"    => CampaignMediaHelper::getMedias($campaign)["{$mediaStep}LoginExhibitionTime"]
        ];

        $options['attr']['step']   = $step;
        $options['attr']['client'] = $client->getId();
        $options['attr']['imageDesktop'] = $medias['imageDesktop'];
        $options['attr']['imageDesktop2'] = $medias['imageDesktop2'];
        $options['attr']['imageDesktop3'] = $medias['imageDesktop3'];


        $options['attr']['imageMobile'] = $medias['imageMobile'];
        $options['attr']['imageMobile2'] = $medias['imageMobile2'];
        $options['attr']['imageMobile3'] = $medias['imageMobile3'];

        $options['attr']['exhibitionTime'] = $medias['exhibitionTime'];

        if ( $medias['imageDesktop'] === $medias['imageMobile']) {
            $medias['imageDesktop'] = null;
        }


        $form = $this->controllerHelper->createForm(
            $formType,
            $entity,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {

            if ($action == self::ON_UPDATE) {
                $this->deleteOldMedia($campaign, $mediaStep);  #TODO SEGUIR AVANCANDO ??:

            }
            if ($request->get('mediaType') == 'video') {
                $skip = $request->get('videoSkip');
                $this->actionVideoSkip($campaign, $skip, $step);
            } elseif ($entity->getFullSize() === null) {
                #TODO CONTAR TIME POR TOTAL DE IMAGENS
                $entity->setFullSize(0);
            }
            if ($request->get('mediaType') == 'image') {
                $totalExibitionTime = $entity->getExhibitionTime();
                $entity->setExhibitionTime($totalExibitionTime);
            }   

            $this->entityManager->persist($entity);
            $this->entityManager->flush();


            if ($step == 'pre-login') {
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('campaign_step_choosing_media', [
                        'id'        => $campaign->getId(),
                        'step'      => 'pos-login',
                        'action'    => $action
                    ])
                );
            }

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('campaign_step_call_to_action', [  #CHAMA PRA COLOCAR O BOTAO DE CTA
                    'id'        => $campaign->getId(),
                    'action'    => $action
                ])
            );
        }

        $defaultGroup = $this->configurationService->getByIdentifierOrDefault(null, $client);

        $attributes = [
            'step'          => $mediaStep,
            'entity'        => $entity,
            'campaign'      => $campaign,
            'form'          => $form->createView(),
            'uploadError'   => false,
            'config'        => $defaultGroup,
            'medias'        => $medias,
            'videoSkip'     => $videoSkip,
            'exhibitionTime'=> $options['attr']['exhibitionTime']
        ];

        if ($request->get('mediaType') == 'video') {
            $campaignDto = CampaignDtoHelper::convert($campaign);
            $videoUrl = $mediaStep == "pre" ? $campaignDto->getPreLoginMediaMobile() : $campaignDto->getPosLoginMediaMobile();
            $mp4Url = $this->getMp4VideoUrl($videoUrl);
            $attributes['videoUrl'] = $mp4Url;
            $attributes['videoOrientation'] = ($mediaStep == "pre")
                ? $campaignDto->getPreLoginOrientation()
                : $campaignDto->getPosLoginOrientation()
            ;
        }

        return $this->render(
            $templateView,
            $attributes
        );
    }

    public function stepCallToActionAction(Request $request, Campaign $campaign)
    {
        /**
         * TODO: sleep adicionado pois no momento em que a tela de upload de campanha redireciona pra cá, não persistiu
         *  o objeto em todos os clusters, com isso o $hasImage fica FALSE e não cai no fluxo do CTA.
         */
        // sleep(10);

        $action     = $request->get('action');
        $hasImage   = CampaignMediaHelper::hasImage($campaign);

        if ($hasImage) {

            $callToAction = $this->entityManager
                ->getRepository('DomainBundle:CampaignCallToAction')
                ->findOneBy([
                    'campaign' => $campaign
                ]);

            if (is_null($callToAction)) {
                $callToAction = new CampaignCallToAction();
            }

            $hasPreLoginImage = CampaignMediaHelper::hasOnPreLogin($campaign);
            $hasPosLoginImage = CampaignMediaHelper::hasOnPosLogin($campaign);

            $form = $this->controllerHelper->createForm(
                CampaignCallToActionType::class,
                $callToAction,
                [
                    'attr' => [
                        'hasPreLoginImage' => $hasPreLoginImage,
                        'hasPosLoginImage' => $hasPosLoginImage
                    ]
                ]
            );

            $form->handleRequest($request);

            if ($form->isValid()) {
                $callToAction->setCampaign($campaign);
                $this->entityManager->persist($callToAction);
                $this->entityManager->flush();

                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('campaign_preview', ['id' => $campaign->getId()])
                );
            }

            $config = $this->configurationService->getDefaultConfiguration($this->getLoggedClient());
            $imageMedias = CampaignMediaHelper::getMedias($campaign);

            return $this->render(
                "AdminBundle:Campaign/steps:step-4-call-to-action.html.twig",
                [
                    'action'            => $action,
                    'config'            => $config,
                    'form'              => $form->createView(),
                    'campaign'          => $campaign,
                    'callToAction'      => $callToAction,
                    'imageMedias'       => $imageMedias,
                    'hasPreLoginImage'  => $hasPreLoginImage,
                    'hasPosLoginImage'  => $hasPosLoginImage
                ]
            );
        }

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('campaign_preview', [ 'id' => $campaign->getId() ])
        );
    }

    public function previewAction(Request $request, Campaign $campaign)
    {
        $campaignDto = CampaignDtoHelper::convert($campaign);

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $config = $this->configurationService->getDefaultConfiguration($this->getLoggedClient());
        $device = $request->get('previewer');

        $step        = $this->defineStepPreview($campaignDto, $request);
        $orientation = ($step == "pre")
            ? $campaignDto->getPreLoginOrientation()
            : $campaignDto->getPosLoginOrientation();

        $view = "AdminBundle:Campaign/steps:step-5-preview.html.twig";
        $videoSkip = $this->getVideoSkip($campaign->getId(), $step);
        $viewParams = [
            'config'        => $config,
            'campaign'      => $campaignDto,
            'template'      => $campaign->getTemplate(),
            'device'        => $device,
            'step'          => $step ? $step : 'pre',
            'callToAction'  => $campaign->getCallToAction(),
            'orientation'   => $orientation,
            'video_skip'    => $videoSkip
        ];

        if (($campaignDto->getPreLoginMediaType() == 'video' && $step == 'pre')
            || ($campaignDto->getPosLoginMediaType() == 'video'
                && $step == 'pos')
        ) {
            $view = "AdminBundle:Campaign/steps:step-5-previewVideo.html.twig";

            $domain = $client->getDomain();

            if ($client->isWhiteLabel()){
                $domain = StringHelper::slugDomain($domain);
            }

            $viewParams['videoUrl'] = $this->generateFileUrl($domain, $step, $campaignDto->getId());
        }

        return $this->render(
            $view,
            $viewParams
        );
    }

    /**
     * @ParamConverter(
     *      "campaign",
     *      class       = "DomainBundle:Campaign",
     *      converter   = "client",
     *      options     = {"message" = "Campanha não encontrada."}
     * )
     */
    public function editAction(Request $request, Campaign $campaign)
    {
        if (!$this->moduleService->modulePermission('campaign')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        return $this->stepGeneralSettingsAction($request, $campaign, self::ON_UPDATE);
    }

    /**
     * @ParamConverter(
     *      "campaign",
     *      class       = "DomainBundle:Campaign",
     *      converter   = "client",
     *      options     = {"message" = "Campanha não encontrada."}
     * )
     * @param Request $request
     * @param Campaign $campaign
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, Campaign $campaign)
    {
        if (!$this->moduleService->modulePermission('campaign')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->campaignService->delete($campaign);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->addCritical("Fail to remove Campaign: {$e->getMessage()}");
            return new JsonResponse(
                [
                    'type'    => "error",
                    'message' => "Não foi possível excluir a Campanha."
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('campaign'));
    }

    /**
     * @param CampaignDto $campaign
     * @param Request $request
     * @return mixed|string
     */
    private function defineStepPreview(CampaignDto $campaign, Request $request)
    {
        $step = $request->get('step');

        if (!$step) {
            if ($campaign->getPreLogin()) {
                $step = 'pre';
            } elseif ($campaign->getPosLogin()) {
                $step = 'pos';
            }
        }
        return $step;
    }

    /**
     * @param Request $request
     * @param Campaign $campaign
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function activateAction(Request $request, Campaign $campaign)
    {
        if (!$this->moduleService->modulePermission('campaign')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $this->campaignService->activate($campaign);
        $this->analyticsService->handler($request, $campaign);

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('campaign'));
    }

    /**
     * @param $form
     * @return \Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroupEntitys
     */
    private function getApsAndGroupsToSave($form)
    {
        $apsAndGroupsId = $form['apsAndGroups']->getData();
        $apsAndGroupsId = $apsAndGroupsId == null ? "[]" : $apsAndGroupsId;
        return $this
            ->searchAccessPointsAndGroups
            ->convertToEntity(json_decode($apsAndGroupsId, true));
    }

    protected function makeMediaTypeHandler($mediaType)
    {
        $className = 'Wideti\DomainBundle\Service\Media\\' .
            preg_replace('/[^A-Za-z0-9]/', '', ucwords($mediaType)) . 'ServiceImp';

        if (!class_exists($className)) {
            throw new \Exception("No media type service found!");
        }

        $clazz = new \ReflectionClass($className);
        return $clazz->newInstance(
            $this->entityManager,
            $this->configurationService,
            $this->validator,
            $this->fileUpload,
            "",
            ""
        );
    }
  public function uploadMediaAction(Request $request)
    {
        $client     = $this->getLoggedClient();
        $campaignId = $request->get('id');
        $step       = $request->get('step');
        $mediaType  = $request->get('mediaType');

        $file       = $request->files->get('file');
        $fileName   = "";
        $originalFileName1 = "";
        $media2   = "";
        $media3   = "";
        $originalFileName2 = "";
        $originalFileName3 = "";
        $teste = 'NAO';


        if ($mediaType == 'image') {
            $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
            $bucket = $this->imageBucket;
            $folder = $this->configurationService->get($nas, $client, 'aws_folder_name');
            $image1 = $request->files->get('image1');
            $image2 = $request->files->get('image2');
            $image3 = $request->files->get('image3');
        }

        $domain = $client->getDomain();

        if ($client->isWhiteLabel()){
            $domain = StringHelper::slugDomain($domain);
        }

        if ($mediaType == 'video') {
            $bucket = $this->videoBucket;
            $folder = 'videos';
            $fileName = "campaign_{$domain}_{$step}_{$campaignId}";
        }

        try {
            if ($file) {
                $mediaService = $this->makeMediaTypeHandler($mediaType);
                $originalFileName1 = $file->getClientOriginalName(); // Obter o nome original do arquivo

                $options = [
                    'type' => $request->get('type'),
                    'fileName' => $fileName
                ];

                $media1 = $mediaService->upload($file, $bucket, $folder, $options);
            } elseif ($image1 || $image2 || $image3) {
                $mediaService = $this->makeMediaTypeHandler($mediaType);
                if ($image1) {
                    $originalFileName1 = $image1->getClientOriginalName();
                    $fileName1 = "campaign_{$domain}_{$step}_{$campaignId}_" . uniqid();
                    $options1 = [
                        'type' => $request->get('type'),
                        'fileName' => $fileName1,
                        'originalFileName' => $originalFileName1
                    ];
                    $media1 = $mediaService->upload($image1, $bucket, $folder, $options1);
                }

                if ($image2) {
                    $originalFileName2 = $image2->getClientOriginalName();
                    $fileName2 = "campaign_{$domain}_{$step}_{$campaignId}_" . uniqid();
                    $options2 = [
                        'type' => $request->get('type'),
                        'fileName' => $fileName2,
                        'originalFileName' => $originalFileName2
                    ];
                    $media2 = $mediaService->upload($image2, $bucket, $folder, $options2);
                }

                if ($image3) {
                    $originalFileName3 = $image3->getClientOriginalName();
                    $fileName3 = "campaign_{$domain}_{$step}_{$campaignId}_" . uniqid();
                    $options3 = [
                        'type' => $request->get('type'),
                        'fileName' => $fileName3,
                        'originalFileName' => $originalFileName3
                    ];
                    $media3 = $mediaService->upload($image3, $bucket, $folder, $options3);
                    $teste = 'salvei teste';
                }

            }else {
                return new JsonResponse(
                    [
                        'message' => 'Selecione um arquivo para upload.',
                        'noFile'  => true,
                    ]
                );
            }


        } catch (HttpException $error) {
            return new JsonResponse(
                [
                    'message' => $error->getMessage(),
                    'error'   => true,
                ]
            );
        }

        return new JsonResponse(
            [
                'message'   => 'Upload realizado com sucesso',
                'fileName'  => $media1,
                'fileName2'  => $media2,
                'fileName3'  => $media3,
                'originalFileName' => $originalFileName1,
                'originalFileName2' => $originalFileName2,
                'originalFileName3' => $originalFileName3,
                'error'     => false,
                'teste' => $teste
            ]
        );
    }
    private function deleteOldMedia(Campaign $campaign, $mediaStep)
    {
        $oldImageMedia = $this
            ->entityManager
            ->getRepository('DomainBundle:CampaignMediaImage')
            ->findBy([
                'campaign'  => $campaign,
                'step'      => $mediaStep
            ]);

        $oldVideoMedia = $this
            ->entityManager
            ->getRepository('DomainBundle:CampaignMediaVideo')
            ->findBy([
                'campaign'  => $campaign,
                'step'      => $mediaStep
            ]);

        foreach ($oldImageMedia as $image) {
            $this->entityManager->remove($image);
            $this->entityManager->flush();
        }

        foreach ($oldVideoMedia as $video) {
            $this->entityManager->remove($video);
            $this->entityManager->flush();
        }
    }

    /**
     * @param $videoUrl
     * @return string
     * Essa função só é utilizada para pegar a url MP4 do vídeo para exibir na tela de pré visualização do admin.
     * Foi a forma encontrada para resolver o problema que tínhamos ao tentar mostrar o vídeo no formato .m3u8.
     * Pois no momento da visualização em alguns casos o vídeo ainda não tinha terminado de ser convertido na AWS.
     */
    private function getMp4VideoUrl($videoUrl)
    {
        if (!$videoUrl) return $videoUrl;
        $exploded = explode('.m3u8', explode('campaign_', $videoUrl)[1])[0];
        return "//{$this->videoBucket}.s3-sa-east-1.amazonaws.com/videos/campaign_{$exploded}.mp4";
    }

    private function generateFileUrl($domain, $step, $campaignId)
    {
        return "//{$this->videoBucket}.s3-sa-east-1.amazonaws.com/videos/campaign_{$domain}_{$step}_{$campaignId}";
    }

    /**
     * Função para deleção de antigo step e criação de novos, ao realizar upload de vídeo.
     * Ao marcar a opção de pular vídeo na dashboard, esta função criará uma entrada na tabela video_skip com a config.
     * @param Campaign $campaign
     * @param $skip // Quantidade de segundos a ser aguardada antes de exibir botão de pular vídeo
     * @param $step // Informa se o skip será utilizado no video de pré ou pós login
     * @return void
     */
    private function actionVideoSkip($campaign, $skip, $step) {
        if ($step == 'pre-login') {
            $videoSkipStep = 'pre';
        } elseif ($step == 'pos-login') {
            $videoSkipStep = 'pos';
        } else {
            $videoSkipStep = null;
        }
        if ($videoSkipStep && $skip && $skip != 0) {
            $this->entityManager->getRepository('DomainBundle:VideoSkip')->deleteByCampaignIdAndStep($campaign->getId(), $videoSkipStep);

            $entityVideoSkip= new VideoSkip();
            $entityVideoSkip->setSkip($skip);
            $entityVideoSkip->setCampaignId($campaign);
            $entityVideoSkip->setStep($videoSkipStep);
            $this->entityManager->persist($entityVideoSkip);
            $this->entityManager->flush();
        }
    }

    private function getVideoSkip($campaignId, $step) {
        if ($step != 'pre' && $step != 'pos') {
            return 0;
        }

        $videoSkip = $this
            ->entityManager
            ->getRepository('DomainBundle:VideoSkip')
            ->findOneBy([
                'campaignId'  => $campaignId,
                'step'      => $step
            ]);
        if ($videoSkip) {
            return $videoSkip->getSkip();
        }
        return 0;

    }
}
