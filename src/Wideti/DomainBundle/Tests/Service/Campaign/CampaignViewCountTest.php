<?php

namespace Wideti\DomainBundle\Tests\Service\Campaign;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Dto\CampaignViewsDto;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignViews;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Repository\CampaignRepository;
use Wideti\DomainBundle\Repository\CampaignViewsRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Campaign\CampaignService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceImp;

class CampaignViewCountTest extends WebTestCase
{
    public function testMustAppendCampaignView()
    {
        $campaign = new Campaign();
        $campaign->setId(1);
        $this->assertTrue($campaign !== null);

        $guest = new Guests();
        $guest->setId(10);
        $this->assertTrue($guest !== null);

        $accessPoint = new AccessPoints();
        $accessPoint->setId(1);
        $this->assertTrue($accessPoint !== null);

        $campaignViewsDto = new CampaignViewsDto();
        $campaignViewsDto->setAccessPoint($accessPoint)
            ->setGuestId($guest)
            ->setType(CampaignViews::STEP_PRE)
            ->setCampaign($campaign);

        $campaignService = $this->getCampaignService();
        $this->assertTrue($campaignService !== null);

        $this->assertTrue($campaignService->saveCampaignView($campaignViewsDto));
    }

    private function getConfigurationServiceMock()
    {
        return $this->getMockBuilder(ConfigurationServiceImp::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getCacheServiceMock()
    {
        return $this->getMockBuilder(CacheServiceImp::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getCampaignViewsRepositoryMock()
    {
        return $this->getMockBuilder(CampaignViewsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getCampaignRepositoryMock()
    {
        return $this->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getCampaignService()
    {
        $configurationService = $this->getConfigurationServiceMock();
        $cacheService = $this->getCacheServiceMock();
        $campaignViewsRepository = $this->getCampaignViewsRepositoryMock();
        $campaignRepository = $this->getCampaignRepositoryMock();

        $campaignService = $this->getMockBuilder(CampaignService::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $configurationService,
                $cacheService,
                $campaignViewsRepository,
                $campaignRepository
            ])
            ->getMock();

        $campaignService->expects($this->once())
            ->method('saveCampaignView')
            ->willReturn(true);

        return $campaignService;
    }
}