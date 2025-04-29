<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\ApiBundle\Helpers\Builder\CampaignVideoUrlBuilder;
use Wideti\DomainBundle\Service\Campaign\CampaignService;
use Wideti\DomainBundle\Service\Client\SelectClientByDomainService;
use Wideti\DomainBundle\Service\Sms\SmsHistoryImp;

class InternalCampaignVideoUrlController implements ApiResource
{
    const RESOURCE_NAME = 'internal_campaign_video_url';

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var CampaignService
     */
    private $campaignService;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * InternalSmsController constructor.
     * @param EntityManager $em
     * @param CampaignService $campaignService
     * @param Logger $logger
     */
    public function __construct(
        EntityManager $em,
        CampaignService $campaignService,
        Logger $logger
    ) {
        $this->em = $em;
        $this->campaignService = $campaignService;
        $this->logger = $logger;
    }

    public function persistUrlAction(Request $request)
    {
        $content = $request->getContent();

        if (!$content) {
            return new JsonResponse(["error" => "Content body is empty"], 400);
        }

        $dto = $this->buildDto($content);
        try {
           $this->campaignService->setCampaignVideoUrl($dto);
        } catch (\Exception $ex) {
            $this->logger->addCritical("Fail to set campaign video URL", [
                "object" => $dto,
                "error"  => $ex->getMessage()
            ]);
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }

        return new JsonResponse($dto, 200);
    }

    private function buildDto($requestContent)
    {
        $callback = json_decode($requestContent, true);
        $builder  = new CampaignVideoUrlBuilder();

        return $builder
            ->withCampaignId($callback["campaignId"])
            ->withType($callback["type"])
            ->withVideoUrl($callback["videoUrl"])
			->withVideoUrlMp4($callback["videoMP4Url"])
            ->withBucketId($callback["bucketId"])
            ->build();
    }

    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
