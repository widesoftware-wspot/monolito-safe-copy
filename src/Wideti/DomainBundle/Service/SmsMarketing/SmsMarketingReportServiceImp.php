<?php

namespace Wideti\DomainBundle\Service\SmsMarketing;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Wideti\DomainBundle\Exception\SmsMarketingReportApiException;
use Wideti\DomainBundle\Service\SmsMarketing\Builder\SmsMarketingReportBuilder;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;

class SmsMarketingReportServiceImp implements SmsMarketingReportService
{
    const ENDPOINT_STATS = '/report';

    /**
     * @var Logger
     */
    private $logger;
    private $apiHost;

    /**
     * SmsMarketingReportServiceImp constructor.
     * @param Logger $logger
     * @param $apiHost
     */
    public function __construct(Logger $logger, $apiHost)
    {
        $this->logger = $logger;
        $this->apiHost = $apiHost;
    }

    public function stats(SmsMarketing $smsMarketing)
    {
        try {
            $stats = $this->request(self::ENDPOINT_STATS, $smsMarketing->getLotNumber());

            return SmsMarketingReportBuilder::getBuilder()
                ->withLot($smsMarketing->getLotNumber())
                ->withTotal($this->valueOrNull($stats, "total"))
                ->withTotalSent($this->valueOrNull($stats, "total_sent"))
                ->withTotalDelivered($this->valueOrNull($stats, "total_delivered"))
                ->withTotalPending($this->valueOrNull($stats, "total_pending"))
                ->withTotalError($this->valueOrNull($stats, "total_error"))
                ->build();
        } catch (SmsMarketingReportApiException $e) {
            $this->logger->addCritical($e->getMessage());
        }

        return $this->emptyReport($smsMarketing);
    }

    /**
     * @param $uri
     * @param $lotNumber
     * @return mixed
     * @throws SmsMarketingReportApiException
     */
    private function request($uri, $lotNumber)
    {
        $url = "{$this->apiHost}{$uri}/{$lotNumber}";
        $httpClient = $this->getHttpClient();
        try {
            $response = $httpClient->request("GET", $url);
        } catch (\Exception $ex) {
            throw new SmsMarketingReportApiException($ex->getMessage(), $lotNumber, $url);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode != 200) {
            throw new SmsMarketingReportApiException($result, $lotNumber, $url);
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function getHttpClient()
    {
        return new GuzzleClient([
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    private function emptyReport(SmsMarketing $smsMarketing)
    {
        return SmsMarketingReportBuilder::getBuilder()
            ->withLot($smsMarketing->getLotNumber())
            ->withTotal($smsMarketing->getTotalSms())
            ->withTotalSent("")
            ->withTotalDelivered("")
            ->withTotalPending("")
            ->withTotalError("")
            ->build();
    }

    private function valueOrNull($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : "-";
    }
}
