<?php

namespace Wideti\DomainBundle\Service\Report;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\ResponseContentHelper;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Report\Reports\Report;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;
use Wideti\DomainBundle\Service\Sns\SnsService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ReportService
{
    use ContainerAwareTrait;
    use SessionAware;
    use LoggerAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use SecurityAware;
    use TwigAware;
    use EntityManagerAware;
    use MongoAware;

    const ONLINE_EXPORT_LIMIT = 1000;

    private $awsKey;
    private $awsSecret;
    private $awsRegion;
    private $awsTopic;

    /**
     * @var SnsService
     */
    private $sns;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    private $bucket;
    private $tempFilePath;
    private $maxReportLinesPoc;
	/**
	 * @var ConfigurationService
	 */
	private $configurationService;

	/**
	 * ReportService constructor.
	 * @param $key
	 * @param $secret
	 * @param $topic
	 * @param $region
	 * @param $bucket
	 * @param $tempFilePath
	 * @param FileUpload $fileUpload
	 * @param $maxReportLinesPoc
	 * @param ConfigurationService $configurationService
	 */
	public function __construct(
    	$key,
	    $secret,
	    $topic,
	    $region,
        $bucket,
	    $tempFilePath,
	    FileUpload $fileUpload,
	    $maxReportLinesPoc,
		ConfigurationService $configurationService
	) {
        $this->awsKey       = $key;
        $this->awsSecret    = $secret;
        $this->awsTopic     = $topic;
        $this->awsRegion    = $region;
        $this->fileUpload   = $fileUpload;

        $this->sns          = new SnsService(
            $this->awsKey,
            $this->awsSecret,
            $this->awsTopic,
            $this->awsRegion
        );
        $this->bucket               = $bucket;
        $this->tempFilePath         = $tempFilePath;
        $this->maxReportLinesPoc    = $maxReportLinesPoc;
		$this->configurationService = $configurationService;
	}

    public function processReport(
        $reportType,
        array $filter,
        Client $client,
        $format = ReportFormat::CSV,
        $charset = null
    ) {
        /**
         * @var Users $user
         */
        $user = $this->getUser();

        $filter['maxReportLinesPoc'] = ($client->getStatus() == Client::STATUS_POC) ? $this->maxReportLinesPoc : null;
        $filter['skip'] = 0;

        $reportService  = $this->getReportService($reportType);
        $countResult    = $reportService->countResult($filter, $client);

        if ($countResult == 0) {
            return 'empty';
        }

        $reportsToBatch = [
            'guest',            // listagem de visitantes
            'access_historic',  // relatório > histórico de acessos
            'sms',              // relatório > sms
            'guests',           // relatório > visitantes
            'birthdays',        // relatório > aniversariantes
	        'call_to_action'    // relatório > campanhas cta
        ];

        if (in_array($reportType, $reportsToBatch) && ($countResult === true || $countResult > $this::ONLINE_EXPORT_LIMIT)) {
            /**
             * @var Client $client
             */
            $client = $this->session->get("wspotClient");

            $filterJson = json_encode($filter);

            $message =
				$reportType 		 . "|" .
                $filterJson 		 . "|" .
                $client->getId() 	 . "|" .
                $user->getUsername() . "|" .
                $format 		     . "|" .
                $charset             . "|" .
				$user->getId();

            try {
                $this->sns->getClient()->publish([
                    "TopicArn" => $this->sns->getArn(),
                    "Message"  => $message
                ]);
            } catch (\Exception $e) {
                $this->logger->addCritical('Fail to send message to SNS. Message: '. $e->getMessage());
            }

            return 'batch';
        }

        $result = $reportService->getReport($charset, $filter, $client, $user, false, $format);

        $fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFilePath, $format);
        $fileBuilder->addContent($result);
        $filePath = $fileBuilder->build();

        $response = $this->getResponseDownload($format, $filePath);
        $fileBuilder->clear();

        return $response;
    }

    public function segmentationExportBatch(ExportDto $exportDto)
    {
        $message = ReportType::SEGMENTATION . "|" .
            $exportDto->getSegmentationId() . "|" .
            $exportDto->getClient() . "|" .
            $exportDto->recipient . "|" .
            ReportFormat::CSV;

        try {
            $this->sns->getClient()->publish([
                "TopicArn" => $this->sns->getArn(),
                "Message"  => $message
            ]);
        } catch (\Exception $e) {
            $this->logger->addCritical('Fail to send message to SNS. Message: '. $e->getMessage());
        }

        return true;
    }

    public function processBatchReport(
        $reportType,
        $filter,
        Client $client,
        Users $user,
        $userEmail,
        $format = ReportFormat::CSV,
        $charset = null
    ) {
        $reportService = $this->getReportService($reportType);

        $result = null;
        try {
            $result = $reportService->getReport($charset, $filter, $client, $user,true, $format);
            $this->sendReportMail($client, $reportType, $result->getFilePath(), $result->getExpireDate(), $userEmail);
        } catch (\Exception $e) {
            $this->logger->addCritical(
                "Erro ao gerar o relatorio batch do tipo " . $reportType . "
                para o cliente (".$client->getDomain()."):" . $e->getMessage()
            );
        }
        return $result;
    }

    /**
     * @param $reportType
     * @return Report
     */
    private function getReportService($reportType)
    {
        $reportLoad = "core.report.{$reportType}";

        /**
         * @var Report $reportService
         */
        $reportService = $this->container->get($reportLoad);

        return $reportService;
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
     * @param Client $client
     * @param $reportType
     * @param $downloadLink
     * @param $expireDate
     * @param $userEmail
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function sendReportMail($client, $reportType, $downloadLink, $expireDate, $userEmail)
    {
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy([
                'username' => $userEmail,
                'client' => $client
            ]);

        $companyName = '';


        if ($client->getType() === Client::TYPE_SIMPLE || $client->getType() === 0) {
	        $partnerName = $this->configurationService->getConfigByKey($client, 'partner_name');
            $companyName = $partnerName['value'];
        }

        $fromEmail = $this->container->getParameter('email_sender');


        $subject = ($reportType == ReportType::SEGMENTATION)
            ? 'Sua exportação de segmentação está pronta'
            : 'O relatório que você solicitou está pronto!'
        ;
        $builder = new MailMessageBuilder();

        if ($client->isWhiteLabel()) {
            $emailTemplate = ($reportType == ReportType::SEGMENTATION)
                ? '@Admin/Report/segmentationFileDownloadWhiteLabel.twig'
                : '@Admin/Report/reportFileDownloadWhiteLabel.twig'
            ;

            $wl = $this->em->getRepository("DomainBundle:WhiteLabel")->findOneBy(['client' => $client]);
            $message = $builder
                ->subject($subject)
                ->from([$wl->getCompanyName() =>  $client->getEmailSenderDefault()])
                ->to([
                    [$userEmail]
                ])
                ->replyTo($client->getEmailSenderDefault())
                ->htmlMessage(
                    $this->renderView(
                        $emailTemplate,
                        [
                            'link'          => $downloadLink,
                            'expireDate'    => $expireDate,
                            'user'          => $user,
                            'client'        => $client,
                            'companyName'   => $companyName,
                            'wl'            => $wl,
                        ]
                    )
                )
                ->build();
        } else {
            $emailTemplate = ($reportType == ReportType::SEGMENTATION)
                ? '@Admin/Report/segmentationFileDownload.twig'
                : '@Admin/Report/reportFileDownload.twig'
            ;
            $message = $builder
                ->subject($subject)
                ->from([$companyName =>  $client->getEmailSenderDefault()])
                ->to([
                    [$userEmail]
                ])
                ->replyTo($client->getEmailSenderDefault())
                ->htmlMessage(
                    $this->renderView(
                        $emailTemplate,
                        [
                            'link'          => $downloadLink,
                            'expireDate'    => $expireDate,
                            'user'          => $user,
                            'client'        => $client,
                            'companyName'   => $companyName
                        ]
                    )
                )
                ->build();
        }

        $this->mailerService->send($message);
    }

    /**
     * @param $folder
     * @return array
     */
    public function getAvailableReportsOnS3($folder)
    {
        $client = $this->getLoggedClient();
        $prefix = "{$client->getId()}/{$folder}";
        $files  = $this->fileUpload->getAllFiles($this->bucket, $prefix);


        return $files;
    }

    /**
     * @param $params
     * @param $reportType
     * @return array
     */
    public static function generateUrlParams($params, $reportType){
        $urlParams = [];
        foreach ($params as $key => $value) {
            if ($key == "fileFormat" || $key == "charset" || $key == "filters")
                continue;
            $urlParams["$reportType"."[$key]"] = "$value";
        }
        return $urlParams;
    }


    public function generateSignedS3Url($folder, $filename)
    {
        $client = $this->getLoggedClient();
        $prefix = "{$client->getId()}/{$folder}";
        return $this->fileUpload->generateSignedReportUrl($prefix, $this->bucket, $filename);
    }

}
