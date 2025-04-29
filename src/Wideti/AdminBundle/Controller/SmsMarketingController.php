<?php

namespace Wideti\AdminBundle\Controller;

use Monolog\Logger;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\AdminBundle\Form\Type\SmsMarketing\SmsMarketingFilterType;
use Wideti\AdminBundle\Form\Type\SmsMarketing\SmsMarketingType;
use Wideti\AdminBundle\Form\Type\SmsMarketing\SmsMarketingFilterGuestType;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\SmsCredit;
use Wideti\DomainBundle\Exception\PhoneFieldNotFoundException;
use Wideti\DomainBundle\Exception\SmsCreditException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\SmsMarketingHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\SmsCredit\SmsCreditService;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;
use Wideti\DomainBundle\Service\SmsMarketing\SmsMarketingReportService;
use Wideti\DomainBundle\Service\SmsMarketing\SmsMarketingService;
use Wideti\DomainBundle\Service\UrlShortner\UrlShortnerReportService;
use Wideti\DomainBundle\Service\UrlShortner\UrlShortnerService;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

/**
 * Class SmsMarketingController
 * @package Wideti\AdminBundle\Controller
 */
class SmsMarketingController
{
    use TwigAware;
    use FlashMessageAware;
    use SecurityAware;

    /**
     * @var ErpService
     */
    private $erpService;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ModuleService
     */
    private $moduleService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var SmsMarketingService
     */
    private $smsMarketingService;
    /**
     * @var SmsMarketingFilterType
     */
    private $smsMarketingFilterType;
    /**
     * @var SmsMarketingReportService
     */
    private $reportService;
    /**
     * @var UrlShortnerReportService
     */
    private $urlShortnerReportService;
    /**
     * @var UrlShortnerService
     */
    private $urlShortnerService;
    /**
     * @var SmsCreditService
     */
    private $smsCreditService;
    /**
     * @var SmsMarketingHelper
     */
    private $smsMarketingHelper;

    /**
     * SmsMarketingController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param ConfigurationService $configurationService
     * @param ModuleService $moduleService
     * @param Logger $logger
     * @param AnalyticsService $analyticsService
     * @param SmsMarketingService $smsMarketingService
     * @param SmsMarketingReportService $reportService
     * @param UrlShortnerReportService $urlShortnerReportService
     * @param UrlShortnerService $urlShortnerService
     * @param SmsCreditService $smsCreditService
     * @param SmsMarketingHelper $smsMarketingHelper
     * @param ErpService $erpService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        ConfigurationService $configurationService,
        ModuleService $moduleService,
        Logger $logger,
        AnalyticsService $analyticsService,
        SmsMarketingService $smsMarketingService,
        SmsMarketingReportService $reportService,
        UrlShortnerReportService  $urlShortnerReportService,
        UrlShortnerService  $urlShortnerService,
        SmsCreditService $smsCreditService,
        SmsMarketingHelper $smsMarketingHelper,
        ErpService $erpService
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->moduleService = $moduleService;
        $this->logger = $logger;
        $this->analyticsService = $analyticsService;
        $this->smsMarketingService = $smsMarketingService;
        $this->smsMarketingFilterType = SmsMarketingFilterType::class;
        $this->reportService = $reportService;
        $this->urlShortnerReportService = $urlShortnerReportService;
        $this->urlShortnerService = $urlShortnerService;
        $this->smsCreditService = $smsCreditService;
        $this->smsMarketingHelper = $smsMarketingHelper;
        $this->erpService = $erpService;
    }

    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('sms_marketing')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $credits = $this->smsCreditService->getAvailableClientCredit($client->getId());

        PlanAssert::checkOrThrow($client, Plan::PRO);

        $options["attr"]["client"] = $this->getLoggedClient()->getId();
        $filterForm = $this->controllerHelper->createForm($this->smsMarketingFilterType, null, $options);
        $filterForm->handleRequest($request);

        if ($filterForm->isValid() && $filterForm->getData()) {
            $filters = $this->smsMarketingService->prepareSearchFilters($filterForm->getData());
        } else {
            $filters = ["status" => "ALL"];
        }

        try {
            $filters["clientId"] = $client->getId();
            $entities = $this->smsMarketingService->search($filters);
        } catch (\Exception $ex) {
            $this->logger->addCritical("SMS MARKETING - Fail to load indexAction()", [
                'error' => $ex->getMessage()
            ]);
            return $this->render(
                'AdminBundle:Admin:error.html.twig'
            );
        }

        return $this->render(
            'AdminBundle:SmsMarketing:index.html.twig',
            [
                "filterForm" => $filterForm->createView(),
                "entities" => $entities,
                "totalCredits" => empty($credits) ? 0 : $credits->getTotalAvailable()
            ]
        );
    }

    public function addCreditAction(Request $request) {
        $slToken = $this->erpService->getToken($this->getUser());
        $slCartUrl = "https://mambo.superlogica.net/clients/areadocliente/publico/carrinhoput?item=49&token={$slToken->getToken()}";
        return $this->controllerHelper->redirect($slCartUrl);
    }

    public function statsAction(Request $request)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $smsMarketingId = (int) $request->get("id");
        $smsMarketing = $this->smsMarketingService->findOne($smsMarketingId);

        if (!$smsMarketing) {
            return $this->render(
                'AdminBundle:Admin:notFound.html.twig',
                [
                    'message' => "Registro não encontrado"
                ]
            );
        }

        if (!$this->moduleService->modulePermission('sms_marketing') || $smsMarketing->getStatus() != SmsMarketing::STATUS_SENT) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        try {
            $stats = $this->reportService->stats($smsMarketing);
        } catch (\Exception $ex) {
            $this->logger->addCritical("SMS MARKETING - Fail to load statsAction()");
            return $this->render(
                'AdminBundle:Admin:error.html.twig'
            );
        }

        $urlOpeningRate = $smsMarketing->getUrlShortnedHash()
            ? $this->urlShortnerReportService->stats($smsMarketing->getUrlShortnedHash())
            : null
        ;

        return $this->render(
            'AdminBundle:SmsMarketing:stats.html.twig',
            [
                "entity"         => $smsMarketing,
                "stats"          => $stats,
                "urlOpeningRate" => $urlOpeningRate
            ]
        );
    }

    public function newAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('sms_marketing')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entity = new SmsMarketing();
        $options["attr"]["clientId"] = $client->getId();
        $form   = $this->controllerHelper->createForm(SmsMarketingType::class, $entity, $options);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $form = $this->validateForm($entity, $form);

            if ($form->getErrors()->count() == 0) {
                try {
                    $entity = $this->smsMarketingService->create($entity);
                } catch (\Exception $e) {
                    $this->logger->addCritical("SMS MARKETING - Fail to create: {$e->getMessage()}");
                    return $this->render(
                        'AdminBundle:Admin:error.html.twig'
                    );
                }

                $this->setCreatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('sms_marketing_edit', ['id' => $entity->getId()]));
            }
        }

        $filterGuestsForm = $this->controllerHelper->createForm(SmsMarketingFilterGuestType::class);

        /**
         * @var SmsCredit $creditAvailable
         */
        $creditAvailable = $this->smsCreditService->getAvailableClientCredit($client->getId());

        return $this->render('AdminBundle:SmsMarketing:form.html.twig', [
            "entity"            => $entity,
            "filterGuestsForm"  => $filterGuestsForm->createView(),
            "form"              => $form->createView(),
            "creditAvailable"   => $creditAvailable ? $creditAvailable->getTotalAvailable() : 0
        ]);
    }

    public function searchGuestsAction(Request $request)
    {
        $client = $this->getLoggedClient();

        try {
            $filter = $this->smsMarketingHelper->prepareTotalGuestFilter(
                $request->get('filter'),
                $client->getDomain()
            );
            $totalGuests = $this->smsMarketingService->filteringTotalGuests($filter);
        } catch (PhoneFieldNotFoundException $ex) {
            return new JsonResponse(
                [
                    "type" => "no_phone_field",
                    "totalGuests" => 0,
                    "query" => ""
                ]
            );
        } catch (\Exception $ex) {
            $this->logger->addCritical("Fail to get total guests to SMS MARKETING: {$ex->getMessage()}");
            return new JsonResponse(
                [
                    "type" => "error"
                ]
            );
        }

        return new JsonResponse(
            [
                "type"          => "success",
                "totalGuests"   => $totalGuests,
                "query"         => json_encode($filter->jsonSerialize())
            ]
        );
    }

    public function urlShortnerAction(Request $request)
    {
        try {
            $fullUrl = $request->get("url");
            $urlShortned = $this->urlShortnerService->shorten($fullUrl);
        } catch (\Exception $ex) {
            $this->logger->addCritical("SMS_MARKETING:: Fail to short URL", [
                "urlToShorten" => $fullUrl,
                "error" => $ex->getMessage()
            ]);
            return new JsonResponse(
                [
                    "type"          => "error",
                    "urlShortned"   => "",
                    "hash"          => ""
                ]
            );
        }

        return new JsonResponse(
            [
                "type"          => "success",
                "urlShortned"   => $urlShortned["urlShortned"],
                "hash"          => $urlShortned["hash"]
            ]
        );
    }

    public function editAction(Request $request)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $smsMarketingId = (int) $request->get("id");
        $smsMarketing = $this->smsMarketingService->findOne($smsMarketingId);

        if (!$this->moduleService->modulePermission('sms_marketing') || $smsMarketing->getStatus() == SmsMarketing::STATUS_SENT) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $options["attr"]["clientId"] = $client->getId();
        $form   = $this->controllerHelper->createForm(SmsMarketingType::class, $smsMarketing, $options);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $form = $this->validateForm($smsMarketing, $form);

            if ($form->getErrors()->count() == 0) {
                $this->smsMarketingService->update($smsMarketing);
                $this->setUpdatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('sms_marketing_edit', ['id' => $smsMarketing->getId()]));
            }
        }

        $filterGuestsForm = $this->controllerHelper->createForm(SmsMarketingFilterGuestType::class, null, [
            'attr' => [
                'query' => $smsMarketing->getQuery()
            ]
        ]);

        /**
         * @var SmsCredit $creditAvailable
         */
        $creditAvailable = $this->smsCreditService->getAvailableClientCredit($client->getId());

        return $this->render('AdminBundle:SmsMarketing:form.html.twig', [
            "entity"            => $smsMarketing,
            "filterGuestsForm"  => $filterGuestsForm->createView(),
            "form"              => $form->createView(),
            "creditAvailable"   => $creditAvailable ? $creditAvailable->getTotalAvailable() : 0
        ]);
    }

    public function deleteAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('sms_marketing')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $smsMarketingId = (int) $request->get("id");
        $smsMarketing = $this->smsMarketingService->findOne($smsMarketingId);

        try {
            $this->smsMarketingService->delete($smsMarketing);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->addCritical("Fail to remove SMS Marketing: {$e->getMessage()}");
            return new JsonResponse(
                [
                    'type'    => "error",
                    'message' => "Não foi possível excluir o registro."
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('sms_marketing'));
    }

    public function sendAction(Request $request)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $smsMarketingId = (int) $request->get("id");
        $smsMarketing = $this->smsMarketingService->findOne($smsMarketingId);

        if (!$smsMarketing) {
            return $this->render(
                'AdminBundle:Admin:notFound.html.twig',
                [
                    'message' => "Registro não encontrado"
                ]
            );
        }

        try {
            $this->smsCreditService->checkIfClientHasEnoughCreditAvailable($client, $smsMarketing->getTotalSms());
            $this->smsMarketingService->sendSmsMessage($smsMarketing);
        } catch (SmsCreditException $e) {
            return new JsonResponse(
                [
                    'type'    => "error",
                    'message' => "Você não possui créditos suficientes para o envio. Refaça o filtro de destinatários."
                ]
            );
        } catch (\Exception $ex) {
            $this->logger->addCritical("Fail to send SMS lot to Microservice");
            $this->setProcessingFailFlashMessage();
            return new JsonResponse(
                [
                    'type'    => "error",
                    'message' => "Ocorreu um erro ao realizar o envio das mensagens, tente novamente!"
                ]
            );
        }

        $this->smsCreditService->consume($client, $smsMarketing->getTotalSms());

        $this->setProcessingSuccessFlashMessage();

        return new JsonResponse(
            [
                'type' => "success"
            ]
        );
    }

    /**
     * @param SmsMarketing $entity
     * @param \Symfony\Component\Form\FormInterface $form
     * @return \Symfony\Component\Form\FormInterface
     */
    private function validateForm(SmsMarketing $entity, \Symfony\Component\Form\FormInterface $form)
    {
        if (!$entity->getQuery()) {
            $form->get('query')->addError(new FormError("É obrigatório selecionar os contatos para envio de SMS."));
        }
        if ($entity->getTotalSms() == 0) {
            $form->get('totalSms')->addError(new FormError("Nenhum contato foi selecionado para envio de SMS."));
        }
        if (!$entity->getMessage()) {
            $form->get('message')->addError(new FormError("É obrigatório informar uma mensagem para envio de SMS."));
        }
        if ($form->get('enableSmsLink')->getData() && (!$entity->getUrlShortnedType() || !$entity->getUrlShortned())) {
            $form->get('urlShortned')->addError(new FormError("Caso queira adicionar uma URL, por favor informe o tipo e a URL desejada."));
        }

        return $form;
    }
}
