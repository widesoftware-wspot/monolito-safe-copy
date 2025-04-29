<?php

namespace Wideti\DomainBundle\Service\Campaign;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\CampaignRepository;

/**
 * Class InactiveCampaignCountService
 * @package Wideti\DomainBundle\Service\Campaign
 */
class InactiveCampaignCountService
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * InactiveCampaignCountService constructor.
     * @param CampaignRepository $campaignRepository
     */
    public function __construct(CampaignRepository $campaignRepository)
    {
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @param Client $client
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function quantity(Client $client)
    {
        return $this->campaignRepository->campaignCountByFilter(
            "client_id = {$client->getId()} AND status = 2"
        );
    }
}