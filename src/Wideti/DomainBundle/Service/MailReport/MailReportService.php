<?php
namespace Wideti\DomainBundle\Service\MailReport;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepositoryAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\MailReport\GoogleChart\GoogleLineChart;
use Wideti\DomainBundle\Service\MailReport\GoogleChart\GoogleMultiLineChart;
use Wideti\DomainBundle\Service\MailReport\GoogleChart\GooglePieChart;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use QuickChart;

class MailReportService
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use SecurityAware;
    use ElasticSearchAware;
    use RadacctReportServiceAware;
    use TranslatorAware;
    use ReportRepositoryAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * MailReportService constructor.
     * @param ConfigurationService $configurationService
     * @param GuestDevices $guestDevices
     */
    public function __construct(ConfigurationService $configurationService, GuestDevices $guestDevices)
    {
        $this->configurationService = $configurationService;
        $this->guestDevices = $guestDevices;
    }

    public function init($client, $amountOfDays)
    {
        $params = $this->prepare($client, $amountOfDays);
        $this->send($params, $client);
    }

    public function prepare(Client $client, $amountOfDays = 7)
    {
        // $amountOfDays   = 7;
        $now            = new \DateTime();
        $lastMonth      = $now->sub(new \DateInterval('P1M'));

        $countTotalSmsLastMonth = $this->em
            ->getRepository('DomainBundle:SmsHistoric')
            ->getSmsBillingByMonth($client, $lastMonth->format('Y-m'));

        $countTotalSmsCurrentMonth = $this->em
            ->getRepository('DomainBundle:SmsHistoric')
            ->getSmsBillingByMonth($client, date('Y-m'));

        $countTotalGuests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countByFilter();

        $countTotalGuestsLastWeek = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countPerPeriod($amountOfDays);

        $uniqueAccessGuests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countUniqueOrReturning('unique', $amountOfDays);

        $returningAccessGuests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countUniqueOrReturning('returning', $amountOfDays);

        $filterRangeDate     = [
            'date_from' => date_format(new \DateTime('now -'.$amountOfDays.' days'), 'Y-m-d 00:00:00'),
            'date_to'   => date_format(new \DateTime('now'), 'Y-m-d 23:59:59')
        ];

        $whiteLabel = $this->em->getRepository('DomainBundle:WhiteLabel')->findBy(['client' => $client]);

        $color = "#ff0000";
        if (array_key_exists("panelColor", $whiteLabel[0])) {
            $color = $whiteLabel[0]["panelColor"];
        }

        $hours               = $this->getAccessByHour($client, $amountOfDays, $color);
        $days                = $this->getVisitsByDay($client, $amountOfDays, $color);
        $osChart             = $this->getOsChart($client, $filterRangeDate, $color);
        $platformChart       = $this->getPlatformChart($client, $filterRangeDate, $color);
        $visitedApsChart     = $this->getMostVisitedAccessPointChart($client, $amountOfDays, false);
        $downloadUpload      = $this->getDownloadUpload($client, $amountOfDays);
        $download            = $downloadUpload['download'];
        $upload              = $downloadUpload['upload'];
        $downloadUploadChart = $this->getDownloadUploadChart($client, $amountOfDays, $color);

        $partnerName = $this->configurationService->getConfigByKey($client, 'partner_name');

        return [
            'amountOfDays'              => $amountOfDays,
            'partner_name'              => $partnerName['value'],
            'smsCost'                   => $client->getSmsCost(),
            'countTotalSmsLastMonth'    => count($countTotalSmsLastMonth),
            'countTotalSmsCurrentMonth' => count($countTotalSmsCurrentMonth),
            'countTotalGuests'          => $countTotalGuests,
            'countTotalGuestsLastWeek'  => $countTotalGuestsLastWeek,
            'uniqueAccessGuests'        => $uniqueAccessGuests,
            'returningAccessGuests'     => $returningAccessGuests,
            'osChart'                   => $osChart,
            'platformChart'             => $platformChart,
            'mostVisitedAp'             => $visitedApsChart,
            'hours'                     => $hours,
            'days'                      => $days,
            'download'                  => $download,
            'upload'                    => $upload,
            'downloadUploadChart'       => $downloadUploadChart,
            'whiteLabel'                => $whiteLabel[0]
        ];
    }

    public function getDownloadUpload($client, $amountOfDays)
    {
        $downloadUploadVar = $this->fieldsDownloadUpload($client);
        $dateFrom = date("Y-m-d", strtotime('-'.$amountOfDays.' days'));

        $search = $this->reportRepository->getDownloadUploadByDate(
            $client,
            [
                'from'  => $dateFrom,
                'to'    => date('Y-m-d')
            ],
            null,
            'download',
            'upload',
            'day',
            'yyyy-MM-dd'
        );

        $downloadUpload = [
            'download' => 0,
            'upload'   => 0
        ];

        if (!isset($search['aggregations']['download_upload']['buckets'])) return false;

        foreach ($search['aggregations']['download_upload']['buckets'] as $data) {
            $downloadUpload['download'] += $data['download']['value'];
            $downloadUpload['upload'] += $data['upload']['value'];
        }

        return $downloadUpload;
    }

    public function getDownloadUploadChart($client, $amountOfDays, $color = "#ff0000")
    {
        $downloadUploadVar = $this->fieldsDownloadUpload($client);
        $dateFrom = date("Y-m-d", strtotime('-'.$amountOfDays.' days'));

        $search = $this->reportRepository->getDownloadUploadByDate(
            $client,
            [
                'from'  => $dateFrom,
                'to'    => date('Y-m-d')
            ],
            null,
            'download',
            'upload',
            'day',
            'yyyy-MM-dd'
        );
        
        if (!isset($search['aggregations']['download_upload']['buckets'])) {
            return false;
        }
        
        $downloadUploadTotal = $search['aggregations']['download_upload']['buckets'];

        $download = [];
        $upload   = [];
        $labels   = [];
        $i        = 0;
        foreach ($downloadUploadTotal as $index) {
            $labels[]   = substr($index['key_as_string'], 8, 2) . '/' . substr($index['key_as_string'], 5, 2);
            $download[] = $this->convertByteToGBorMB($index['download']['value']);
            $upload[]   = $this->convertByteToGBorMB($index['upload']['value']);
        }

        if (empty($download) && empty($upload)) {
            return false;
        }

        $datasets[] = ["label" => "Download", "data" => $download, "borderColor" => $color];
        $datasets[] = ["label" => "Upload", "data" => $upload, "borderColor" => "green"];
        $width = 520;
        if ($amountOfDays > 7) {
            $width = 1000;
        }
        $dimension = ["width" => $width, "height" => 200];
        $chartUrl = $this->getChartsUrl("line", $dimension, $labels, $datasets);
        return $chartUrl;
    }

    /**
     * @param $client
     * @return array
     */
    private function fieldsDownloadUpload($client)
    {
        $download = "acctoutputoctets";
        $upload   = "acctinputoctets";

        $routerMode = $this->configurationService->getConfigByKey($client, 'router_mode');

        if ($routerMode['value'] != "router") {
            $download = "acctinputoctets";
            $upload   = "acctoutputoctets";
        }

        return [
            'download' => $download,
            'upload'   => $upload
        ];
    }

    private function convertByteToGBorMB($bytes)
    {
        return round(($bytes/1024/1024));
    }

    public function getVisitsByDay($client, $amountOfDays, $color = "#ff0000")
    {
        $date_from  = date_format(new \DateTime("NOW -".$amountOfDays." days"), 'Y-m-d');
        $date_to    = date_format(new \DateTime("NOW"), 'Y-m-d');

        $visits = $this->reportRepository->getAllVisitsAndRegistersPerDay(
            $client,
            [
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'filtered'  => true
            ]
        );

        if (!isset($visits['aggregations']['visits_records_per_day']['buckets'])) return false;

        $data = $visits['aggregations']['visits_records_per_day']['buckets'];

        $labels = [];
        $i = 0;
        foreach ($data as $row) {
            $labels[$i] = $row["key_as_string"];
            $guests[$i] = $row["totalVisits"]["value"];
            $i++;
        }

        if (empty($guests)) {
            return false;
        }

        $datasets = [["label" => "", "data" => $guests, "borderColor" => $color]];

        $width = 520;
        if ($amountOfDays > 7) {
            $width = 1000;
        }
        $dimension = ["width" => $width, "height" => 150];

        $chartUrl = $this->getChartsUrl("line", $dimension, $labels, $datasets);

        return $chartUrl;
    }

    public function getAccessByHour($client, $amountOfDays, $color = "#ff0000")
    {
        $date_from  = date_format(new \DateTime("NOW -".$amountOfDays." days"), 'Y-m-d');
        $date_to    = date_format(new \DateTime("NOW"), 'Y-m-d');

        $visits = $this->reportRepository->mostAccessedHoursByClient(
            $client,
            [
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'filtered'  => true
            ]
        );

        $labels = [];
        $data = [];

        for ($i=0; $i<24; $i++) {
            $labels[$i] = $i;
            $data[$i] = 0;
        }

        if (!isset($visits['access_by_hour_visits']['buckets'])) return false;

        foreach ($visits['access_by_hour_visits']['buckets'] as $row) {
            $data[intval($row['key'])] = $row['totalVisits']['value'];
        }

        if (empty($data)) {
            return false;
        }

        $datasets = [["label" => "", "data" => $data, "borderColor" => $color]];
        $dimension = ["width" => 520, "height" => 150];

        $chartUrl = $this->getChartsUrl("line", $dimension, $labels, $datasets);

        return $chartUrl;
    }

    public function getOsChart($client, $filterRangeDate, $color = "#ff0000")
    {
        $devices = $this->guestDevices->accessData($client, 'os', $filterRangeDate);

        $accessData['deviceData'] = [];

        foreach ($devices as $data) {
            foreach ($data as $value) {
                array_push(
                    $accessData['deviceData'],
                    [
                        'label' => $value['os'],
                        'data' => (integer)$value['total']
                    ]
                );
            }
        }

        $data   = [];
        $total  = 0;

        foreach ($accessData['deviceData'] as $row) {
            $total += $row['data'];
        }

        $labels = [];
        $i = 0;
        foreach ($accessData['deviceData'] as $row) {
            $labels[$i] = $row['label'];
            $data[$i] = sprintf("%.2f", ($row['data'] * 100) / $total);
            $i++;
        }

        if (empty($data)) {
            return false;
        }

        $datasets = [["label" => "", "data" => $data, "backgroundColor" => ['silver', $color]]];
        $dimension = ["width" => 520, "height" => 200];

        $chartUrl = $this->getChartsUrl("pie", $dimension, $labels, $datasets);

        return $chartUrl;
    }

    public function getPlatformChart($client, $filterRangeDate, $color = "#ff0000")
    {
        $devices = $this->guestDevices->accessData($client, 'platform', $filterRangeDate);
        $accessData['guestPlatform'] = [];

        foreach ($devices as $data) {
            foreach ($data as $value) {
                array_push(
                    $accessData['guestPlatform'],
                    [
                        'label' => $value['platform'],
                        'data' => (integer)$value['total']
                    ]
                );
            }
        }

        $data = [];
        $total  = 0;

        foreach ($accessData['guestPlatform'] as $row) {
            $total += $row['data'];
        }

        $labels = [];
        $i = 0;
        foreach ($accessData['guestPlatform'] as $row) {
            $labels[$i] = $row['label'];
            $data[$i] = sprintf("%.2f", ($row['data'] * 100) / $total);
            $i++;
        }

        if (empty($data)) {
            return false;
        }

        $datasets = [["label" => "", "data" => $data, "backgroundColor" => ['silver',$color]]];
        $dimension = ["width" => 520, "height" => 200];

        $chartUrl = $this->getChartsUrl("pie", $dimension, $labels, $datasets);

        return $chartUrl;
    }

    public function getMostVisitedAccessPointChart(Client $client, $amountOfDays, $useCache = true)
    {
        $date_from  = date_format(new \DateTime("NOW -".$amountOfDays." days"), 'Y-m-d');
        $date_to    = date_format(new \DateTime("NOW"), 'Y-m-d');

        $visits = $this->reportRepository->getVisitsAndRecordsPerAccessPoint(
            $client,
            [
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'filtered'  => true
            ],
            [],
            5
        );

        $results = [];
        $qtdeAps = 1;

        foreach ($visits as $data) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->findOneBy([
                    'friendlyName' => $data['key'],
                    'client'       => $client,
                    'status'       => AccessPoints::ACTIVE
                ]);

            $accessPointName = $data['key'];

            if ($accessPoint) {
                $accessPointName = StringHelper::textOverflow($accessPoint->getFriendlyName(), 19);
            }

            if ($accessPoint && $accessPoint->getStatus() == AccessPoints::ACTIVE && $qtdeAps <= 5) {
                array_push(
                    $results,
                    [
                        'label' => $accessPointName,
                        'data'  => $data['totalVisits']['value']
                    ]
                );
                $qtdeAps++;
            }
        }

        $total = 0;
        $data = [];

        foreach ($results as $visit) {
            $total += $visit['data'];
        }

        $labels = [];
        $i = 0;
        if ($total > 0) {
            foreach ($results as $visit) {
                $labels[$i] = $visit['label'];
                $data[$i] = sprintf("%.2f", ($visit['data'] * 100) / $total);
                $i++;
            }
        }

        $datasets = [["label" => "", "data" => $data]];
        $dimension = ["width" => 520, "height" => 200];

        $chartUrl = $this->getChartsUrl("pie", $dimension, $labels, $datasets);

        return $chartUrl;
    }

    public function send($params, Client $client)
    {
        $partnerName = $this->configurationService->getConfigByKey($client, 'partner_name');

        $clientDomain = $client->getDomain();

        $isWhiteLabel = false;
        if (strpos($clientDomain, '.')) {
            $isWhiteLabel = true;
        }
        else {
            $clientDomain .= ".mambowifi.com";
        }

        $params['isWhiteLabel'] = $client->isWhiteLabel();
        $params['clientDomain'] = $clientDomain;

        $subject = "{$this->translator->trans('mail_report.subject')} - {$clientDomain}";

        $fromEmail = $client->getEmailSenderDefault();
        $from = ['Mambo WiFi' => $fromEmail];

        if ($isWhiteLabel) {
            $subject = "{$this->translator->trans('mail_report.wl_subject')} - {$clientDomain}";

            $from = [$partnerName['value'] => $fromEmail];
            $contactEmail = '';
        }else{
            $contactEmail = "suporte@mambowifi.com";
        }

        foreach ($this->getEmails($client) as $userEmail => $language) {
            $this->translator->setLocale($language);
            $template = $this->render(
                'AdminBundle:Report:mail_report.html.twig',
                [
                    'obj'           => $params,
                    'amountOfDays'  => $params['amountOfDays'],
                    'contact_email'    => $contactEmail
                ]
            );

            $builder = new MailMessageBuilder();
            $message = $builder
				->subject($subject)
				->from($from)
                ->to([
                    [$userEmail]
                ])
                ->htmlMessage($template->getContent())
                ->tracking('OpenEmailTracking')
                ->identifier(date('Y-m-d'))
                ->build()
            ;

            $this->mailerService->send($message);
        }

        $this->setMailReportSent($client);
    }

    public function getEmails($client)
    {
        $users = $this->em
            ->getRepository('DomainBundle:Users')
            ->findBy(
                [
                    'receiveReportMail' => 1,
                    'client'            => $client->getId(),
                    'status'            => Users::ACTIVE
                ]
            )
        ;

        $emails = [];

        foreach ($users as $user) {
            $emails[$user->getUsername()] = ($user->getReportMailLanguage()) ? 'en' : 'pt_br';
        }

        return $emails;
    }

    public function setMailReportSent(Client $client)
    {
        $client->setReportSent(true);
        $this->em->persist($client);
        $this->em->flush();
    }

    public function getChartsUrl($type, $dimension, $labels, $datasets)
    {
        if ($dimension === null || $dimension === "")
            $dimension = ["width" => 520, "height" => 200];

        $display = false;
        if (count($datasets) > 1 || $type === "pie")
            $display = true;

        for ($i = 0;$i < count($datasets);$i++)
            if (array_key_exists("borderColor", $datasets[$i]))
                $datasets[$i]["fill"] = false;

        $config = [
            "type" => $type,
            "data" => [
                "labels" => $labels,
                "datasets" => $datasets
            ],
            "options" => [
                "legend" => [
                    "display" => $display
                ]
            ]
        ];

        $config = json_encode($config);
        
        $chart = new QuickChart($dimension);
        $chart->setConfig($config);
        $chartUrl = $chart->getUrl();

        return $chartUrl;
    }
}
