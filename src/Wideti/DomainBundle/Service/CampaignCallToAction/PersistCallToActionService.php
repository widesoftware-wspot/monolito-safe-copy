<?php

namespace Wideti\DomainBundle\Service\CampaignCallToAction;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Form\Form;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignCallToAction;
use Wideti\DomainBundle\Repository\CampaignRepository;
use Wideti\DomainBundle\Repository\CampaignCallToAction\PersistCallToActionRepository;

/**
 * Class CreateService
 * @package Wideti\DomainBundle\Service\CampaignCallToAction
 */
class PersistCallToActionService
{
    /**
     * @var PersistCallToActionRepository
     */
    private $persistCallToActionRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CreateCallToActionService constructor.
     * @param CampaignRepository $campaignRepository
     * @param Logger $logger
     */
    public function __construct(
        PersistCallToActionRepository $persistCallToActionRepository,
        Logger $logger
    )
    {
        $this->persistCallToActionRepository = $persistCallToActionRepository;
        $this->logger = $logger;
    }

    /**
     * @param CampaignCallToAction $callToAction
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist(CampaignCallToAction $callToAction)
    {
        $this->persistCallToActionRepository->persist($callToAction);
    }
}