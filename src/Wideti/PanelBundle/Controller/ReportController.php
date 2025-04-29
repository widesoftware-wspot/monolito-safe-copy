<?php

namespace Wideti\PanelBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\ResponseContentHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportServiceAware;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\DomainBundle\Service\Watchdog\ClientsAreNotUsingReport;
use Wideti\PanelBundle\Helpers\PaginationHelper;
use Wideti\PanelBundle\Service\SuperLogicaService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Entity\Client;

class ReportController
{
    use EntityManagerAware;
    use TwigAware;
    use ReportServiceAware;

    /**
     * @var PaginationHelper
     */
    private $paginationHelper;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var DocumentManager $dm
     */
    private $dm;

    /**
     * @var ElasticSearch $elasticSearchService
     */
    private $elasticSearchService;

    /**
     * @var ClientsAreNotUsingReport $clientsAreNotUsingReport
     */
    private $clientsAreNotUsingReport;

    /**
     * @var SuperLogicaService $superLogicaService
     */
    private $superLogicaService;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    private $tempFileFolder;

    /**
     * ReportController constructor.
     * @param PaginationHelper $paginationHelper
     * @param ContainerInterface $container
     * @param DocumentManager $dm
     * @param ElasticSearch $elasticSearchService
     * @param ClientsAreNotUsingReport $clientsAreNotUsingReport
     * @param SuperLogicaService $superLogicaService
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     */
    public function __construct(
        PaginationHelper $paginationHelper,
        ContainerInterface $container,
        DocumentManager $dm,
        ElasticSearch $elasticSearchService,
        ClientsAreNotUsingReport $clientsAreNotUsingReport,
        SuperLogicaService $superLogicaService,
        FileUpload $fileUpload,
        $tempFileFolder
    ) {
        $this->paginationHelper = $paginationHelper;
        $this->container = $container;
        $this->dm = $dm;
        $this->elasticSearchService = $elasticSearchService;
        $this->clientsAreNotUsingReport = $clientsAreNotUsingReport;
        $this->superLogicaService = $superLogicaService;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
    }

    /**
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function clientsTestingAction(Request $request, $page = 1)
    {
        $limitFilter = $this->paginationHelper->getPagination()->limitPageFilter((int)$request->get('limitFilter'));

        $searchBy = $request->get('search_by');

        $query = $this->em
            ->getRepository('DomainBundle:Client')
            ->listAllClientsQuery($searchBy);

        $paginator  = $this->container->get('knp_paginator');

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', $request->get('page'))/*page number*/,
            $limitFilter/*limit per page*/
        );

        $numPocClients = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllPocClients();

        $session  = $this->container->get("session");

        return $this->render('PanelBundle:Report:clientsTesting.html.twig', [
            'numPocClients' => $numPocClients,
            'pagination' => $pagination,
            'session' => $session
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function domainsAction(Request $request)
    {
        $clients = $this->em
            ->getRepository('DomainBundle:Client')
            ->findAll();

        $domainsGuests = [];

        foreach ($clients as $client) {
            $database = StringHelper::slugDomain($client->getDomain());
            $db = $this->dm->getConnection()->selectDatabase($database);
            $collection = $db->selectCollection('guests');
            $result = $collection->find()->count();
            $domainsGuests[$client->getDomain()] = $result;
        }

        arsort($domainsGuests);
        $domainsGuestsSorted = [];

        foreach($domainsGuests as $key => $value) {
            $domainsGuestsSorted[$key] = $value;
        }

        $domainsGuestsArraySize          = 10;
        $domainsPerAccessPointsArraySize = 10;
        $domainsSignUpFlag               = $request->get('domain_signup_flag');
        $hideTableFlag                   = false;

        if ($request->get('limitFilter') != null) {
            $domainsGuestsArraySize = (int)$request->get('limitFilter');
            $domainsPerAccessPointsArraySize = (int)$request->get('limitFilter');
        }

        if ($domainsSignUpFlag == 'yes') {
            $hideTableFlag = true;
        }

        $domainsGuestsSorted = array_slice($domainsGuestsSorted, 0, $domainsGuestsArraySize);

        $domainsPerAccessPoints = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->accessPointsPerDomainsResult();

        $domainsPerAccessPoints = array_slice($domainsPerAccessPoints, 0, $domainsPerAccessPointsArraySize);

        $totalAccessPoints = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->countAllAccessPoints();

        $totalActiveAccessPoints = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->countAllActiveAccessPoints();

        $totalInactiveAccessPoints = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->countAllInactiveAccessPoints();

        $totalDomains = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllDomains();

        $totalActiveDomains = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllActiveClients();

        $totalInactiveDomains = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllInactiveClients();

        $accessPointsPerDomains = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->accessPointsPerDomains();

        $numPocClients = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllPocClients();

        $numPocDomains = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->countAccessPointsPOC();

        return $this->render('PanelBundle:Report:domain.html.twig', [
            'accessPointsPerDomains'    => $accessPointsPerDomains,
            'totalAccessPoints'         => $totalAccessPoints,
            'totalActiveAccessPoints'   => $totalActiveAccessPoints,
            'totalInactiveAccessPoints' => $totalInactiveAccessPoints,
            'totalDomains'              => $totalDomains,
            'totalActiveDomains'        => $totalActiveDomains,
            'totalInactiveDomains'      => $totalInactiveDomains,
            'numPocClients'             => $numPocClients,
            'numPocDomains'             => $numPocDomains,
            'domainsPerAccessPoints'    => $domainsPerAccessPoints,
            'domainsGuestsSorted'       => $domainsGuestsSorted,
            'hideTableFlag'             => $hideTableFlag,
            'result'                    => $result
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function clientsAreNotUsingAction(Request $request)
    {
        $status                             = $request->get('statusFilter');
        $clientsAreNotUsingReport           = $this->clientsAreNotUsingReport->execute(true, $status);
        $totalClientsAreNotUsing            = count($clientsAreNotUsingReport);
        $clientsAreNotUsingReportArraySize  = 10;

        if ($request->get('limitFilter') != null) {
            $clientsAreNotUsingReportArraySize = (int)$request->get('limitFilter');
        }

        $clientsAreNotUsingReport = array_slice($clientsAreNotUsingReport, 0, $clientsAreNotUsingReportArraySize);

        return $this->render(
            'PanelBundle:Report:clientsAreNotUsing.html.twig',
            [
                'clientsAreNotUsingReport' => $clientsAreNotUsingReport,
                'totalClientsAreNotUsing'  => $totalClientsAreNotUsing
            ]
        );
    }

    public function clientsAreNotUsingExport(Request $request)
    {
        $clientsAreNotUsingReport = $this->clientsAreNotUsingReport->execute(true, 1);

        $content    = [];
        $columns    = [];

        $columns[]  = 'Razão Social';
        $columns[]  = 'ERP ID';
        $columns[]  = 'Domínio';
        $columns[]  = 'Dias sem acesso';
        $columns[]  = 'Último acesso';
        $columns[]  = 'Status';
        $columns[]  = 'E-mail Admins';

        foreach ($clientsAreNotUsingReport as $data) {
            $row = [];
            /**
             * @var Client $client
             */
            $client = $data['client'];
            array_push($row, $client->getCompany());
            array_push($row, $client->getErpId());
            array_push($row, $client->getDomain());
            array_push($row, $data['days_without_access']);
            array_push($row, $data['last_access']);
            array_push($row, $client->getStatusAsString());
            array_push($row, $data['user_email']);

            array_push($content, $row);
        }

        $report = new ReportDto();
        $report->setColumns($columns);
        $report->setContent($content);

        $fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, ReportFormat::XLSX);
        $fileBuilder->addContent($report);
        $filePath = $fileBuilder->build();

        $response = $this->getResponseDownload(ReportFormat::XLSX, $filePath);
        $fileBuilder->clear();

        return $response;
    }

    /**
     * @param $format
     * @param $filePath
     * @return Response
     */
    private function getResponseDownload($format, $filePath)
    {
        $response = new ResponseContentHelper();

        return $response->getDownloadResponseByFileFormat($filePath, $format);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function paymentAction(Request $request)
    {
        $searchBy               = $request->get('searchBy');
        $paymentsData           = $this->superLogicaService->processDebtQueryBySearchFilter($searchBy);
        $totalYesterdayPayments = $this->superLogicaService->processAmountOfYesterdayPayments($paymentsData);
        $yesterdayDate          = $this->superLogicaService->getYesterdayDate();
        $totalPayments          = count($paymentsData);
        $paymentArraySize       = 100;

        if ($request->get('limitFilter') != null) {
            $paymentArraySize = (int)$request->get('limitFilter');
        }

        $paymentsData = array_slice($paymentsData, 0, $paymentArraySize);

        return $this->render('PanelBundle:Report:payment.html.twig', [
            'searchBy'               => $searchBy,
            'paymentsData'           => $paymentsData,
            'totalYesterdayPayments' => $totalYesterdayPayments,
            'yesterdayDate'          => $yesterdayDate,
            'totalPayments'          => $totalPayments
        ]);
    }

    public function clientsFeaturesAction(Request $request)
    {
        $searchBy = $request->get('search_by');

        $arrayLoginIds              = [];
        $arrayCreatedTemplateIds    = [];
        $arrayCreatedCampaignsIds   = [];
        $arrayAccountingsIds        = [];
        $arrayBandwidthIds          = [];

        $clients     = $this->em->getRepository('DomainBundle:Client')->findBy(['status' => Client::STATUS_ACTIVE]);
        $limitFilter = $request->get('limitFilter');

        $clientsArraySize = 10;
        if (!is_null($limitFilter)) {
            $clientsArraySize = (int)$request->get('limitFilter');
        }

        foreach ($clients as $client) {
            $mongoClient    = $this->dm->getConnection()->getMongoClient();
            $clientDatabase = StringHelper::slugDomain($client->getDomain());
            $database       = $mongoClient->$clientDatabase;
            $collection     = $database->groups;
            $search = $collection->find();
            $clientId = $client->getId();

            if ($searchBy == 'bandwidth' || is_null($searchBy)) {
                foreach ($search as $item) {
                    foreach ($item['configurations'] as $configItem) {
                        foreach ($configItem['configurationValues'] as $configurationValue) {
                            if ($configurationValue['key'] == 'enable_bandwidth' && $configurationValue['value'] == '1') {
                                if (!in_array($clientId, $arrayBandwidthIds)) {
                                    array_push($arrayBandwidthIds, $clientId);
                                }
                            }
                        }
                    }
                }
                if ($searchBy == 'bandwidth' || !is_null($limitFilter)) {
                    $clients = $this->em->getRepository('DomainBundle:Client')->findById($arrayBandwidthIds);
                }
            }

            if ($searchBy == 'login' || is_null($searchBy)) {
                $usersThatLoggedIn = $this->em->getRepository('DomainBundle:Users')->findBy(['client' => $clientId]);
                foreach ($usersThatLoggedIn as $user) {
                    $userId = $user->getClient()->getId();
                    $ultimoAcesso = $user->getUltimoAcesso();
                    if (!is_null($ultimoAcesso)) {
                        if (!in_array($userId, $arrayLoginIds)) {
                            array_push($arrayLoginIds, $userId);
                        }
                    }
                }
                if ($searchBy == 'login' || !is_null($limitFilter)) {
                    $clients = $this->em->getRepository('DomainBundle:Client')->findById($arrayLoginIds);
                }
            }

            if ($searchBy == 'accounting' || is_null($searchBy)) {
                $elasticResults = $this->elasticSearchService->search(
                    'radacct',
                    [
                        "size" => 0,
                        "query"=>[
                            "bool"=>[
                                "must"=>[
                                    [
                                        "term"=>["client_id"=>$clientId]],
                                    [
                                        "range"=>[
                                            "acctstarttime"=>[
                                                "gte"=>"2015-01-01 00:00:00"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    ElasticSearch::ALL
                );
                if ($elasticResults['hits']['total'] != 0) {
                    array_push($arrayAccountingsIds, $clientId);
                }
                if ($searchBy == 'accounting' || !is_null($limitFilter)) {
                    $clients = $this->em->getRepository('DomainBundle:Client')->findById($arrayAccountingsIds);
                }
            }

        }

        if ($searchBy == 'campaign' || is_null($searchBy)) {
            $clientsCampaign = $this->em->getRepository('DomainBundle:Campaign')->getClientsThatHasNotMadeCampaign();
            foreach ($clientsCampaign as $clientCampaign) {
                foreach ($clientCampaign as $userId) {
                    if (!in_array($userId, $arrayCreatedCampaignsIds)) {
                        array_push($arrayCreatedCampaignsIds, $userId);
                    }
                }
            }

            if ($searchBy == 'campaign' || !is_null($limitFilter)) {
                $clients = $this->em->getRepository('DomainBundle:Client')->findById($arrayCreatedCampaignsIds);
            }
        }

        if ($searchBy == 'template' || is_null($searchBy)) {
            $usersIdThatCreatedTemplate = $this->em->getRepository('DomainBundle:Template')->getClientIdsThatHaveCreatedTemplates();
            foreach ($usersIdThatCreatedTemplate as $userIdsTemplate) {
                foreach ($userIdsTemplate as $userId) {
                    array_push($arrayCreatedTemplateIds, $userId);
                }
            }
            if ($searchBy == 'template' || !is_null($limitFilter)) {
                $clients = $this->em->getRepository('DomainBundle:Client')->findById($arrayCreatedTemplateIds);
            }
        }

        $clients = array_slice($clients, 0, $clientsArraySize);

        $totalLogins        = count($arrayLoginIds);
        $totalTemplates     = count($arrayCreatedTemplateIds);
        $totalCampaigns     = count($arrayCreatedCampaignsIds);
        $totalAccountings   = count($arrayAccountingsIds);
        $totalBandwidths    = count($arrayBandwidthIds);

        return $this->render(
            '@Panel/Report/clientsFeatures.html.twig',
            [
                'clients'                   => $clients,
                'arrayLoginIds'             => $arrayLoginIds,
                'arrayCreatedTemplateIds'   => $arrayCreatedTemplateIds,
                'arrayCreatedCampaignsIds'  => $arrayCreatedCampaignsIds,
                'arrayAccountingsIds'       => $arrayAccountingsIds,
                'arrayBandwidthIds'         => $arrayBandwidthIds,
                'searchBy'                  => $searchBy,
                'totalLogins'               => $totalLogins,
                'totalTemplates'            => $totalTemplates,
                'totalCampaigns'            => $totalCampaigns,
                'totalAccountings'          => $totalAccountings,
                'totalBandwidths'           => $totalBandwidths
            ]
        );
    }
}
