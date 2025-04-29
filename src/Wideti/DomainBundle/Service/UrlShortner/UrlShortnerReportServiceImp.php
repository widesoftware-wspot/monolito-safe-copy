<?php

namespace Wideti\DomainBundle\Service\UrlShortner;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Wideti\DomainBundle\Exception\UrlShortnerApiException;

class UrlShortnerReportServiceImp implements UrlShortnerReportService
{
    const ENDPOINT_STATS = '/stats';

    /**
     * @var Logger
     */
    private $logger;
    private $curl;
    private $apiHost;

    /**
     * UrlShortnerReportServiceImp constructor.
     * @param Logger $logger
     * @param $apiHost
     */
    public function __construct(Logger $logger, $apiHost)
    {
        $this->logger = $logger;
        $this->apiHost = $apiHost;
    }

    /**
     * @param $hash
     * @return string
     * @throws UrlShortnerApiException
     */
    public function stats($hash)
    {
        $uri = $this->apiHost . self::ENDPOINT_STATS;

        try {
            $response = $this->request("GET", $uri, $hash);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getCode() !== 404) {
                $this->logger->addCritical($e->getMessage());
            }
            return $this->emptyReport();
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode === 404) {
            return $this->emptyReport();
        }

        if ($statusCode !== 200 || !isset($result["totalAccess"])) {
            throw new UrlShortnerApiException($result, $hash, $uri);
        }

        return $result["totalAccess"];
    }

    private function request($action, $uri, $hashUrl)
    {
        $httpClient = $this->getHttpClient();
        return $httpClient->request($action, "{$uri}/{$hashUrl}");
    }

    /**
     * @return mixed
     */
    private function getHttpClient()
    {
        return new GuzzleClient([
            'verify' => false,
            'base_uri' => $this->apiHost,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    private function emptyReport()
    {
        return 0;
    }
}
