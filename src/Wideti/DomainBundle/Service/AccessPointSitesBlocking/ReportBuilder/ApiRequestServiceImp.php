<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder;

use GuzzleHttp\Client as GuzzleClient;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Exception\SitesBlockingApiException;

class ApiRequestServiceImp implements ApiRequestService
{
    const ENDPOINT_BLOCKED_CATEGORIES       = '/top-blocked-categories';
    const ENDPOINT_MOST_ACCESSED_CATEGORIES = '/top-accessed-categories';
    const ENDPOINT_BLOCKED_DOMAINS          = '/top-blocked-domains';
    const ENDPOINT_MOST_ACCESSED_DOMAINS    = '/top-accessed-domains';

    /**
     * @var Logger
     */
    private $logger;
    private $curl;
    private $apiHost;

    /**
     * ApiRequestServiceImp constructor.
     * @param Logger $logger
     * @param $apiHost
     */
    public function __construct(Logger $logger, $apiHost)
    {
        $this->logger = $logger;
        $this->apiHost = $apiHost;
    }

    public function request($uri)
    {
        $httpClient = $this->getHttpClient();
        $fullUri = "{$this->apiHost}{$uri}";

        try {
            $response = $httpClient->request("GET", $uri);
        } catch (\Exception $ex) {
            throw new SitesBlockingApiException($ex->getMessage(), null, $fullUri);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode != 200) {
            throw new SitesBlockingApiException($result, null, $fullUri);
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function getHttpClient()
    {
        return $this->curl = new GuzzleClient([
            'verify' => false,
            'base_uri' => "http://{$this->apiHost}",
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }
}
