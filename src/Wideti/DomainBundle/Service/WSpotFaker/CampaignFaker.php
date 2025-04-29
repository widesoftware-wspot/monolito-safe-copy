<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignHours;
use Wideti\DomainBundle\Entity\CampaignViews;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\CampaignHoursRepository;
use Wideti\DomainBundle\Repository\CampaignRepository;
use Wideti\DomainBundle\Repository\CampaignViewsRepository;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Helpers\FakerHelper;

class CampaignFaker implements WSpotFaker
{
    private $bucket;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var ClientRepository
     */
    private $clientRepository;
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;
    /**
     * @var CampaignHoursRepository
     */
    private $campaignHoursRepository;
    /**
     * @var CampaignViewsRepository
     */
    private $campaignViewsRepository;

    /**
     * CampaignFaker constructor.
     * @param $bucket
     * @param EntityManager $entityManager
     * @param FileUpload $fileUpload
     * @param ClientRepository $clientRepository
     * @param CampaignRepository $campaignRepository
     * @param CampaignHoursRepository $campaignHoursRepository
     * @param CampaignViewsRepository $campaignViewsRepository
     */
    public function __construct(
        $bucket,
        EntityManager $entityManager,
        FileUpload $fileUpload,
        ClientRepository $clientRepository,
        CampaignRepository $campaignRepository,
        CampaignHoursRepository $campaignHoursRepository,
        CampaignViewsRepository $campaignViewsRepository
    ) {
        $this->entityManager = $entityManager;
        $this->fileUpload = $fileUpload;
        $this->clientRepository = $clientRepository;
        $this->campaignRepository = $campaignRepository;
        $this->campaignHoursRepository = $campaignHoursRepository;
        $this->campaignViewsRepository = $campaignViewsRepository;
    }

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $faker = FakerHelper::faker();
        $clientMySql = $this->clientRepository->findOneBy([ 'id' => $client->getId() ]);

        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_ACTIVE);
        $campaign->setClient($clientMySql);
        $campaign->setName("Campanha {$faker->colorName}");
        $campaign->setSsid(strtolower($faker->colorName));
        $campaign->setRedirectUrl('https://www.wspot.com.br');
        $campaign->setBgColor($faker->hexColor);

        $campaign->setInAccessPoints(false);

        $campaign->setStartDate((new \DateTime('NOW'))->modify('-5 days'));
        $campaign->setEndDate((new \DateTime('NOW'))->modify('+10 days'));

        $campaignHour = new CampaignHours();
        $campaignHour->setCampaign($campaign);
        $campaignHour->setStartTime('00:00');
        $campaignHour->setEndTime('23:59');
        $campaign->addCampaignHour($campaignHour);

        $campaign->setPreLogin(true);
        $campaign->setPreLoginImageTime(5);
        $campaign->setPreFullSize(true);
        $campaign->setPreLoginImageDesktop('campanha_fake/campanha_fake_1024x768.jpeg');
        $campaign->setPreLoginImageMobile('campanha_fake/campanha_fake_768x1024.jpeg');

        $campaign->setPosLogin(true);
        $campaign->setPosLoginImageTime(5);
        $campaign->setPosFullSize(true);
        $campaign->setPosLoginImageDesktop('campanha_fake/campanha_fake_1024x768.jpeg');
        $campaign->setPosLoginImageMobile('campanha_fake/campanha_fake_768x1024.jpeg');

        $this->generateCampaignViews($campaign, $faker);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        $oldFolder = "demo/campanha_fake";
        $newFolder = "{$client->getDomain()}/campanha_fake";
        $this->fileUpload->copyFileBetweenFolders($this->bucket, $oldFolder, $newFolder);

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $this->campaignViewsRepository->deleteAllByClient($client);
        $this->campaignHoursRepository->deleteAllByClient($client);
        $this->campaignRepository->deleteAllByClient($client);
        $this->fileUpload->deleteAllFiles($this->bucket, "{$client->getDomain()}/campanha_fake");

        return true;
    }

    private function generateCampaignViews($campaign, $faker)
    {
        $viewType = [
            CampaignViews::STEP_PRE,
            CampaignViews::STEP_POS
        ];

        for ($i=0; $i<100; $i++) {
            $campaignView = new CampaignViews();
            $campaignView->setCampaign($campaign);
            $campaignView->setType($viewType[array_rand($viewType, 1)]);
            $campaignView->setDateTime($faker->dateTimeBetween('-4 days', 'now'));
            $campaignView->setAccessPoint($faker->macAddress);
            $campaignView->setGuest('F8-16-54-FD-39-50');

            $this->entityManager->persist($campaignView);
        }
    }
}
