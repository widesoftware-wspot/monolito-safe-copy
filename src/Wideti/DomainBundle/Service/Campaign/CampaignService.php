<?php

namespace Wideti\DomainBundle\Service\Campaign;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Bridge\Monolog\Logger;
use Wideti\ApiBundle\Helpers\Dto\CampaignVideoUrlDto;
use Wideti\DomainBundle\Dto\CampaignViewsDto;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignViews;
use Wideti\DomainBundle\Helpers\CampaignMediaHelper;
use Wideti\DomainBundle\Helpers\CampaignDtoHelper;
use Wideti\DomainBundle\Repository\CampaignRepository;
use Wideti\DomainBundle\Repository\CampaignViewsRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Media\MediaService;
use Wideti\DomainBundle\Service\Sns\SnsService;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

/**
 * Class CampaignService
 * @package Wideti\DomainBundle\Service\Campaign
 */
class CampaignService
{
    const PRE_LOGIN = 'PreLogin';
    const POS_LOGIN = 'PosLogin';

    use SecurityAware;
    use SessionAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CampaignViewRepository
     */
    private $campaignViewRepository;
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;
    /**
     * @var Logger
     */
	private $logger;
    /**
     * @var EntityManager
     */
	private $entityManager;
    /**
     * @var SnsService
     */
	private $snsService;
    /**
     * @var MediaService
     */
    private $mediaImageService;
    /**
     * @var MediaService
     */
    private $mediaVideoService;
    /**
     * @var array
     */
    private $webhookAllowedDomains;
    /**
     * @var bool
     */
    private $isWebhookEnable;
    /**
     * @var string
     */
    private $matchspotSNSHook;

    /**
     * CampaignService constructor.
     * @param ConfigurationService $configurationService
     * @param CacheServiceImp $cacheService
     * @param CampaignViewsRepository $campaignViewRepository
     * @param CampaignRepository $campaignRepository
     * @param Logger $logger
     * @param EntityManager $entityManager
     * @param SnsService $snsService
     * @param MediaService $mediaImageService
     * @param MediaService $mediaVideoService
     * @param $webhookAllowedDomains
     * @param $isWebhookEnable
     * @param $matchspotSNSHook
     */
    public function __construct (
        ConfigurationService $configurationService,
        CacheServiceImp $cacheService,
        CampaignViewsRepository $campaignViewRepository,
        CampaignRepository $campaignRepository,
        Logger $logger,
        EntityManager $entityManager,
        SnsService $snsService,
        MediaService $mediaImageService,
        MediaService $mediaVideoService,
        $webhookAllowedDomains,
        $isWebhookEnable,
        $matchspotSNSHook
    ) {
        $this->configurationService     = $configurationService;
        $this->cacheService             = $cacheService;
        $this->campaignViewRepository   = $campaignViewRepository;
        $this->campaignRepository       = $campaignRepository;
        $this->logger                   = $logger;
        $this->entityManager            = $entityManager;
        $this->snsService               = $snsService;
        $this->webhookAllowedDomains    = $webhookAllowedDomains;
        $this->isWebhookEnable          = $isWebhookEnable;
        $this->matchspotSNSHook         = $matchspotSNSHook;
        $this->mediaImageService        = $mediaImageService;
        $this->mediaVideoService        = $mediaVideoService;
    }

	/**
	 * @param Campaign $campaign
	 * @return bool
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function create(Campaign $campaign)
    {
        if (!$campaign->getClient()) {
            $client = $this->entityManager
                ->getRepository("DomainBundle:Client")
                ->find($this->getLoggedClient());

            if ($client == null) {
                throw new NotFoundException('Client not found');
            }

            $campaign->setClient($client);
        }

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        if (!$this->entityManager->contains($campaign)) {
            $this->logger->addCritical(
                "It is impossible to verify if the campaign was created for client ". $client->getId()
            );
            return false;
        }

        if ($this->cacheService->isActive()) {
            $this->cacheService->remove(CacheServiceImp::TEMPLATE_BY_CAMPAIGN);
        }

        return true;
    }

    /**
     * @param Campaign $campaign
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Campaign $campaign)
    {
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
        }
    }

    /**
     * @param Campaign $campaign
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(Campaign $campaign)
    {
        if (CampaignMediaHelper::hasImage($campaign)) {
            $this->mediaImageService->remove($campaign);
        }

        if (CampaignMediaHelper::hasVideo($campaign)) {
            $this->mediaVideoService->remove($campaign);
        }

        $this->entityManager->remove($campaign);
        $this->entityManager->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
        }
    }

    /**
     * @param Campaign $campaign
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function activate(Campaign $campaign)
    {
        $campaign->setStatus(1);

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
        }
    }

    /**
     * @param CampaignViewsDto $campaignView
     * @return bool
     */
    public function saveCampaignView(CampaignViewsDto $campaignView)
    {
        $this->session->set($this->session->getId(), []);
        $sessionId = $this->session->get($this->session->getId(), []);

        if (!$sessionId) {
            $sessionId = [];
            $this->session->set($this->session->getId(), []);
            $sessionId = $this->session->get($this->session->getId(), []);
        }

        $campaignSessionValue = $campaignView->getType() . ":" . $campaignView->getCampaign();

        if (!in_array($campaignSessionValue, $sessionId, true)) {
            array_push($sessionId, $campaignSessionValue);
            $this->session->set($this->session->getId(), $sessionId);

            $campaign = $this->campaignRepository->findOneBy([
                'id' => $campaignView->getCampaign()
            ]);

            if (!$campaign) {
                $this->logger->addCritical('Campaign not found on SaveCampaignView', [
                    'campaignId' => $campaignView->getCampaign()
                ]);
                return false;
            }

            $this->save($campaign, $campaignView);
        }
    }

    /**
     * @param Campaign $campaign
     * @param CampaignViewsDto $campaignView
     */
    public function save(Campaign $campaign, CampaignViewsDto $campaignView)
    {
        $view = new CampaignViews();
        $view->setCampaign($campaign);
        $view->setType($campaignView->getType());

        if ($campaignView->getGuestId()) {
            $view->setGuestId($campaignView->getGuestId());
        }

        if ($campaignView->getGuestMacAddress()) {
            $view->setGuestMacAddress($campaignView->getGuestMacAddress());
        }

        if ($campaignView->getAccessPoint()) {
            $view->setAccessPoint($campaignView->getAccessPoint());
        }

        try {
            $this->campaignViewRepository->save($view);
            $this->sendToMatchspotWebHook($view);
        } catch (\Exception $ex) {
            $this->logger->addCritical(
                "Campaign count failed, type: {$campaignView->getType()}}, ex: {$ex->getMessage()}"
            );
        }
    }

    /**
     * @param CampaignViews $view
     * TODO Método que irá enviar WEB hook de forma experimental para os cliente MatchSpot e MatchSpot-Hapvida
     *  usando o SNS. ISSUE WSPOTNEW-3009 para maiores detalhes, isso aqui é um toggle feature, deverá ser migrado
     *  ou atualizado em breve
     */
    public function sendToMatchspotWebHook(CampaignViews $view)
    {
        try {
            $client = $this->getLoggedClient();
            $domain = $client->getDomain();

            if (in_array($domain, $this->webhookAllowedDomains) && $this->isWebhookEnable) {
                $messagePayload = $this->prepareHookMessage($view, $domain);
                $sns = $this->snsService->getClient();
                $sns->publish([
                    "TopicArn" => $this->matchspotSNSHook,
                    "Message"  => $messagePayload
                ]);
            }
        } catch (\Exception $ex) {
            $this->logger->addCritical(
                "Campaign count failed, type: {$view->getType()}}, ex: {$ex->getMessage()}"
            );
        }
    }

    public function prepareHookMessage(CampaignViews $view, $domain)
    {
        return json_encode([
            "id"                    => $view->getId(),
            "campaignId"            => $view->getCampaign() !== null ? $view->getCampaign()->getId() : null,
            "type"                  => $view->getType() === 1 ? "PRE_LOGIN" : "POS_LOGIN",
            "timestamp"             => $view->getDateTime()->getTimestamp(),
            "timeZone"              => $view->getDateTime()->getTimezone()->getName(),
            "guestId"               => $view->getGuestId(),
            "guestMacAddress"       => $view->getGuestMacAddress(),
            "accessPointIdentifier" => $view->getAccessPoint(),
            "domain"                => $domain
        ]);
    }

    /**
     * @param $campaignId
     * @return null|object
     */
    public function getById($campaignId)
    {
        if (!$campaignId) {
            return null;
        }

        return $this->entityManager->getRepository('DomainBundle:Campaign')
                ->findOneBy([
                    'id' => $campaignId
                ]);
    }

    /**
     * @param array $filterForm
     * @return array
     */
    public function prepareCampaignFilters(array $filterForm)
    {
        $filters["client"] = $this->getLoggedClient();

        if (!empty($filterForm["name"])) {
            $filters["name"] = $filterForm["name"];
        }

        if (isset($filterForm["status"])) {
            $filters["status"] = $filterForm["status"];
        }

        if (!empty($filterForm["start_date"])) {
            $filters["startDate"] = $filterForm["start_date"];
        }

        if (!empty($filterForm["end_date"])) {
            $filters["endDate"] = $filterForm["end_date"];
        }

        if (!empty($filterForm["type"])) {
            switch ($filterForm["type"]) {
                case Campaign::STEP_PRE_LOGIN:
                    $filters["step"] = 'pre';
                    break;
                case Campaign::STEP_POS_LOGIN:
                    $filters["step"] = 'pos';
                    break;
            }
        }

        if (count($filterForm["access_points"]) > 0) {
            foreach ($filterForm["access_points"] as $accessPointId) {
                $filters["access_points"][] = $accessPointId;
            }
        }

        return $filters;
    }

    /**
     * @param $filters
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCampaignByFilter($filters)
    {
        $campaigns = [];
        $entities = $this->campaignRepository->getCampaignByFilter($filters);

        foreach ($entities as $entity) {
            $campaign = CampaignDtoHelper::convert($entity);

            if (isset($filters['step']) && $filters['step'] == Campaign::STEP_PRE_LOGIN) {
                if (!$campaign->getPosLogin()) {
                    array_push($campaigns, $campaign);
                }
            } elseif (isset($filters['step']) && $filters['step'] == Campaign::STEP_POS_LOGIN) {
                if (!$campaign->getPreLogin()) {
                    array_push($campaigns, $campaign);
                }
            } else {
                array_push($campaigns, $campaign);
            }
        }

        return $campaigns;
    }

    public function setCampaignVideoUrl(CampaignVideoUrlDto $dto)
    {

        try {
            $campaign = $this->entityManager
                ->getRepository('DomainBundle:Campaign')->findOneBy([
                    'id' => $dto->getCampaignId()
                ]);

            $campaignVideo = $this->entityManager
                ->getRepository('DomainBundle:CampaignMediaVideo')
                ->findOneBy([
                    'campaign' => $campaign,
                    'step' => $dto->getType()

                ]);

            if ($campaignVideo) {
                $campaignVideo->setUrl($dto->videoUrl);
				$campaignVideo->setUrlMp4($dto->getVideoMp4Url());
                $campaignVideo->setBucketId($dto->bucketId);
                $this->entityManager->persist($campaignVideo);
                $this->entityManager->flush();
            }
        } catch (\Exception $ex) {
            return $ex;
        }
    }
}
