<?php

namespace Wideti\DomainBundle\Service\SmsMarketing;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Exception\SmsMarketingApiException;
use Wideti\DomainBundle\Helpers\SmsMarketingHelper;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\TotalGuestsFilter;
use Wideti\DomainBundle\Service\SmsMarketing\Util\SmsMarketingUtil;

use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;

class SmsMarketingServiceImp implements SmsMarketingService
{
    const ENDPOINT_BASE     = '/management';
    const ENDPOINT_LIST     = '/management/list';
    const ENDPOINT_NEW      = '/management/new';
    const ENDPOINT_FILTER   = '/management/filter';
    const ENDPOINT_UPDATE   = '/management/update';
    const ENDPOINT_DELETE   = '/management';
    const ENDPOINT_SEND     = '/management';

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var auditLogService
     */
    private $auditLogService;

    /**
     * @var SmsMarketingHelper
     */
    private $smsMarketingHelper;
    private $curl;
    private $apiHost;
    private $user;

    /**
     * SmsMarketingServiceImp constructor.
     * @param ContainerInterface $container
     * @param Session $session
     * @param SmsMarketingHelper $smsMarketingHelper
     * @param Logger $logger
     * @param $apiHost
     */
    public function __construct(
        ContainerInterface $container,
        Session $session,
        SmsMarketingHelper $smsMarketingHelper,
        Logger $logger,
        $apiHost,
        AuditLogService $auditLogService
    ) {
        $this->session = $session;
        $this->container = $container;
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        $this->smsMarketingHelper = $smsMarketingHelper;
        $this->logger = $logger;
        $this->apiHost = $apiHost;
        $this->auditLogService = $auditLogService;
    }

    public function findOne($smsMarketingId)
    {
        $client = $this->getLoggedClient();
        $uri = self::ENDPOINT_BASE . "/{$smsMarketingId}" . "/clients/" . $client->getId();

        try {
            $response = $this->apiGet($uri);
        } catch (\Exception $e) {
            $this->logError("Fail to get sms marketing", [
                "error" => $e->getMessage()
            ]);
            return null;
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200) {
            $this->logError("Fail to get sms marketing", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
            return null;
        }

        return SmsMarketingUtil::convertToObject($result);
    }

    public function search(array $filters)
    {
        $response = $this->apiPost(self::ENDPOINT_LIST, [
            "clientId" => $filters["clientId"],
            "status" => $filters["status"]
        ]);

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200) {
            $this->logError("Fail to get a list of sms marketing", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
            return [];
        }

        $entities = [];
        foreach ($result as $item) {
            $smsMarketing = SmsMarketingUtil::convertToObject($item);
            array_push($entities, $smsMarketing);
        }

        return $entities;
    }

    public function prepareSearchFilters(array $filterForm)
    {
        $filters = [];

        if (isset($filterForm["status"])) {
            $filters["status"] = $filterForm["status"];
        } else {
            $filters["status"] = "ALL";
        }

        return $filters;
    }

    public function filteringTotalGuests(TotalGuestsFilter $filter)
    {
        try {
            $response = $this->apiPost(self::ENDPOINT_FILTER, $filter->jsonSerialize());
        } catch (\Exception $e) {
            throw new SmsMarketingApiException($e->getMessage(), self::ENDPOINT_FILTER);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200) {
            $this->logError("Fail to filter total guests", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
            return 0;
        }

        return $result["total"];
    }

    public function create(SmsMarketing $smsMarketing)
    {
        $smsMarketing->setClientId($this->getLoggedClient()->getId());
        $smsMarketing->setStatus(SmsMarketing::STATUS_DRAFT);
        $smsMarketing->setAdminUserId($this->user->getId());

        $uri = self::ENDPOINT_NEW;

        try {
            $response = $this->apiPost($uri, $smsMarketing->jsonSerialize());
        } catch (\Exception $e) {
            throw new SmsMarketingApiException($e->getMessage(), $uri);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200) {
            $this->logError("Fail to create sms marketing", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
            return null;
        }

        $this->auditLogService->createAuditLog(
            'sms-marketing',
            Events::create()->getValue(),
            null,
            true
        );

        return SmsMarketingUtil::convertToObject($result);
    }

    private function getLoggedClient()
    {
        return $this->session->get("wspotClient");
    }

    public function update(SmsMarketing $entity)
    {
        $uri = self::ENDPOINT_UPDATE . "/{$entity->getId()}";

        try {
            $response = $this->apiPut($uri, $entity->jsonSerialize());
        } catch (\Exception $e) {
            throw new SmsMarketingApiException($e->getMessage(), $uri);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200) {
            $this->logError("Fail to update sms marketing", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
            return null;
        }

        $this->auditLogService->createAuditLog(
            'sms-marketing',
            Events::update()->getValue(),
            null,
            true
        );

        return SmsMarketingUtil::convertToObject($result);
    }

    public function delete(SmsMarketing $smsMarketing)
    {
        $client = $this->getLoggedClient();
        $uri = self::ENDPOINT_DELETE . "/{$smsMarketing->getId()}/clients/{$client->getId()}";

        try {
            $response = $this->apiDelete($uri);
        } catch (\Exception $e) {
            throw new SmsMarketingApiException($e->getMessage(), $uri);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 204) {
            $this->logError("Fail to delete sms marketing", [
                "statusCode" => $statusCode,
                "content" => json_encode($result)
            ]);
        }

        $this->auditLogService->createAuditLog(
            'sms-marketing',
            Events::delete()->getValue(),
            null,
            true
        );
    }

    public function sendSmsMessage(SmsMarketing $smsMarketing)
    {
        $client = $this->getLoggedClient();

        $filter = $this->smsMarketingHelper->prepareTotalGuestFilter(
            json_decode($smsMarketing->getQuery(), true),
            $client->getDomain()
        );

        $uri = self::ENDPOINT_SEND . "/{$smsMarketing->getId()}/clients/{$client->getId()}";

        try {
            $response = $this->apiPost($uri, $filter->jsonSerialize());
        } catch (\Exception $e) {
            throw new SmsMarketingApiException($e->getMessage(), $uri);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->logError("Fail to send sms marketing", [
                "statusCode" => $statusCode
            ]);
        }

        $this->auditLogService->createAuditLog(
            'sms-marketing',
            Events::send()->getValue(),
            null,
            true
        );

    }

    private function apiPost($uri, $body)
    {
        $url = "{$this->apiHost}{$uri}";
        $httpClient = $this->getHttpClient();
        return $httpClient->request("POST", $url, [
            "body" => json_encode($body)
        ]);
    }

    private function apiPut($uri, $body)
    {
        $httpClient = $this->getHttpClient();
        $url = "{$this->apiHost}{$uri}";
        return $httpClient->request("PUT", $url, [
            "body" => json_encode($body)
        ]);
    }

    private function apiGet($uri)
    {
        $httpClient = $this->getHttpClient();
        $url = "{$this->apiHost}{$uri}";
        return $httpClient->request("GET", $url);
    }

    private function apiDelete($uri)
    {
        $httpClient = $this->getHttpClient();
        $url = "{$this->apiHost}{$uri}";
        return $httpClient->request("DELETE", $url);
    }

    private function getHttpClient()
    {
        return new GuzzleClient([
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    private function logError($message, $context)
    {
        $this->logger->addCritical("SMS MARKETING Monolith - " . $message, $context);
    }
}
