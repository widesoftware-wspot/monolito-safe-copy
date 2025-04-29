<?php

namespace Wideti\DomainBundle\Service\RadiusPolicy;

use Dompdf\Exception;
use Monolog\Logger;
use Wideti\DomainBundle\Gateways\Sessions\PostSessionGateway;
use Wideti\DomainBundle\Service\Cache\PolicyCacheServiceImp;
use Wideti\DomainBundle\Service\S3Service\S3Service;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicy;

class RadiusPolicyServiceImp implements RadiusPolicyService
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;
    /**
     * @var PolicyCacheServiceImp
     */
    private $cacheService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var S3Service
     */
    private $s3Service;
    /**
     * @var string PolicyBucket
     */
    private $s3PoliciesBucket;
    /**
     * @var PostSessionGateway
     */
    private $postSessionGateway;

    /**
     * RadiusPolicyServiceImp constructor.
     * @param ElasticSearch $elasticSearch
     * @param PolicyCacheServiceImp $cacheService
     * @param Logger $logger
     * @param S3Service $s3Service
     * @param $s3PoliciesBucket
     * @param PostSessionGateway $postSessionGateway
     */
    public function __construct(
        ElasticSearch $elasticSearch,
        PolicyCacheServiceImp $cacheService,
        Logger $logger,
        S3Service $s3Service,
        $s3PoliciesBucket,
        PostSessionGateway $postSessionGateway
    ) {
        $this->elasticSearch = $elasticSearch;
        $this->cacheService = $cacheService;
        $this->logger = $logger;
        $this->s3Service = $s3Service;
        $this->s3PoliciesBucket = $s3PoliciesBucket;
        $this->postSessionGateway = $postSessionGateway;
    }

    /**
     * @param RadiusPolicy $policy
     * @return RadiusPolicy
     * @throws Exception
     */
    public function save(RadiusPolicy $policy)
    {
        try {
            #$this->sendToElastic($policy);
            $this->sendToCache($policy);
            $this->sendSessionToService($policy);
        } catch (\Exception $ex) {
            $this->logger->addCritical(
                'Falha ao gerar Policy',
                [
                    'policy' => json_encode($policy->toArray())
                ]
            );
            $this->logger->addCritical($ex->getMessage());
            throw new Exception($ex);
        }

        return $policy;
    }

    private function sendToElastic(RadiusPolicy $policy)
    {
        $date  = new \DateTime();
        $index = "radius_policy_{$date->format('Y')}_{$date->format('m')}";

        return $this
            ->elasticSearch
            ->index('policy', $policy->toArray(), $policy->getId(), $index);
    }

    private function prepareToOCI($id, RadiusPolicy $policy)
    {

        $timeLimit      = $policy->getTimeLimit();
        $bandwidth      = $policy->getBandwidth();
        $accessPoint    = $policy->getAccessPoint();
        $client         = $policy->getClient();
        $guest          = $policy->getGuest();
        $created        = $policy->getCreated();

        $policyArray = [
            "id" => $id,
            "client" => [
                "id" => (int) $client->getId(),
                "plan" => $client->getPlan(),
                "apCheck" => (bool) $client->isApCheck()
            ],
            "guest" => [
                "username" => (int) $guest->getUsername(),
                "password" => $guest->getPassword(),
                "employee" => (bool) $guest->getEmployee()
            ],
            "accessPoint" => [
                "calledStationName" => $accessPoint->getCalledStationName(),
                "calledStationId" => $accessPoint->getCalledStationId(),
                "callingStationId" => $accessPoint->getCallingStationId(),
                "vendorName" => $accessPoint->getVendorName(),
                "routerMode" => $accessPoint->getRouterMode(),
                "timezone" => $accessPoint->getTimezone()
            ],
            "bandwidth" => [
                "download" => $bandwidth->getDownload(),
                "upload" => (int) $bandwidth->getUpload(),
                "hasLimit" => (int) $bandwidth->isHasLimit()
            ],
            "timeLimit" => [
                "module" => $timeLimit->getModule(),
                "time" => (int) $timeLimit->getTime(),
                "hasTime" => (bool) $timeLimit->isHasTime()
            ],
            "created" => (int) \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $created)->getTimestamp() * 1000 // milliseconds
        ];

        return json_encode($policyArray);
    }

    private function sendToCache(RadiusPolicy $radiusPolicy)
    {
        if (!$this->cacheService->isActive()) return;

        $key = PolicyCacheServiceImp::RADIUS_POLICY_KEY . "{$radiusPolicy->getId()}";
        // se o expire/ttl sofrer alguma alteração, lembrar de alterar no wspot-aaa -> src/daos/PolicyDaoImp.php
        $this->cacheService->set(
            $key,
            $radiusPolicy->toArray(),
            PolicyCacheServiceImp::TTL_RADIUS_POLICY,
            false,
            true
        );
    }

    private function sendSessionToService(RadiusPolicy $radiusPolicy) {
        $policyArray = $this->prepareToOCI($radiusPolicy->getId(), $radiusPolicy);
        $policyArray = json_decode($policyArray, true);
        $policyArray['bandwidth']['hasLimit'] = (bool) $policyArray['bandwidth']['hasLimit'];
        $policyArray['created'] = date('Y-m-d H:m:s', \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $radiusPolicy->getCreated())->getTimestamp()) ;
        $this->postSessionGateway->post($policyArray);
    }
}
