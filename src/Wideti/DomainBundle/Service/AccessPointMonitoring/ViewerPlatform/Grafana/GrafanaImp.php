<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana;

use GuzzleHttp\Client as GuzzleClient;
use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto\FolderDto;
use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto\ResponseDto;

class GrafanaImp implements Grafana
{
    private $grafanaUrl;
    private $grafanaApiKey;
    private $curl;

    /**
     * GrafanaImp constructor.
     * @param $grafanaUrl
     * @param $grafanaApiKey
     */
    public function __construct($grafanaUrl, $grafanaApiKey)
    {
        $this->grafanaUrl = $grafanaUrl;
        $this->grafanaApiKey = $grafanaApiKey;

        $this->curl = new GuzzleClient([
            'base_uri'      => "{$this->grafanaUrl}/api/",
            'http_errors'   => false,
            'headers'       => [
                'Authorization' => "Bearer {$this->grafanaApiKey}",
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function getFolderByName($folderName)
    {
        $response   = $this->curl->request("GET", "folders/{$folderName}");
        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        return new ResponseDto($statusCode, $result);
    }

    public function createFolder(FolderDto $dto)
    {
        $response = $this->curl
            ->request(
                'POST',
                "folders",
                [
                    'body' => json_encode($dto)
                ]
            );

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        return new ResponseDto($statusCode, $result);
    }

    public function createDashboard($schema)
    {
        $response = $this->curl
            ->request(
                'POST',
                "dashboards/db",
                [
                    'body' => $schema
                ]
            );

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        return new ResponseDto($statusCode, $result);
    }

    public function removeDashboard($uid)
    {
        $response = $this->curl->request('DELETE', "dashboards/uid/{$uid}");

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        return new ResponseDto($statusCode, $result);
    }
}
