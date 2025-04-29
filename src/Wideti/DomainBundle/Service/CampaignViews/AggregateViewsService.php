<?php


namespace Wideti\DomainBundle\Service\CampaignViews;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Wideti\DomainBundle\Entity\CampaignViewsAggregated;
use Wideti\DomainBundle\Service\CampaignViews\Builder\AggregatedViewsBuilder;
use Wideti\DomainBundle\Service\CampaignViews\Dto\AggregatedViewsDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\DeleteRequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\DeleteGuestFromS3Imp;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;

class AggregateViewsService
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * AggregateViewsService constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     */
    public function __construct(EntityManager $entityManager, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function execute($dateFrom = null, $dateTo = null, $clientId = null)
    {
        if (!$dateFrom || !$dateTo) {
            $lastAggregated = $this->entityManager
                ->getRepository("DomainBundle:CampaignViewsAggregated")
                ->getLastAggregation();

            if (!$lastAggregated) {
                $this->logger->addCritical("Fail to get last campaign view aggregated");
                die();
            }

            $lastAggregationTime = new \DateTime($lastAggregated['last_aggregation_time']);

            $dateFrom = $lastAggregationTime->add(new \DateInterval("P1D"))->setTime(0, 0, 0);
            $dateTo   = (new \DateTime())->sub(new \DateInterval("P1D"));
        }

        $interval   = \DateInterval::createFromDateString('1 day');
        $period     = new \DatePeriod($dateFrom, $interval, $dateTo);

        foreach ($period as $date) {
            $startDate  = $date->format("Y-m-d 00:00:00");
            $endDate    = $date->format("Y-m-d 23:59:59");

            try {
                $views = $this->entityManager
                    ->getRepository("DomainBundle:CampaignViews")
                    ->getAggregatedCountBetweenDates($startDate, $endDate, $clientId);
            } catch (\Exception $ex) {
                $this->logger->addCritical("Fail to get campaign views itens by dates: {$ex->getMessage()}");
            }

            try {
                foreach ($views as $view) {
                    $builder = new AggregatedViewsBuilder();
                    $item = $builder
                        ->withClientId($view['client_id'])
                        ->withCampaignId($view['campaign_id'])
                        ->withStep($view['step'])
                        ->withLastAggregatedTime($endDate)
                        ->withTotal($view['total'])
                        ->build()
                    ;

                    $this->save($item);
                }
            } catch (\Exception $ex) {
                $this->logger->addCritical(
                    "Fail to aggregate and save campaign views: {$ex->getMessage()}",
                    [
                        "date_from" => $startDate,
                        "date_to"   => $endDate
                    ]
                );
            }
        }
    }

    private function save(AggregatedViewsDto $viewsDto)
    {
        $lastAggregated = $this->entityManager
            ->getRepository('DomainBundle:CampaignViewsAggregated')
            ->getLastItemAggregatedByCondition($viewsDto);

        if ($lastAggregated) {
            $totalViews = $lastAggregated['total'] + $viewsDto->getTotal();

            $this->entityManager
                ->getRepository('DomainBundle:CampaignViewsAggregated')
                ->updateExistingRecord(
                    $lastAggregated['id'],
                    $viewsDto->getLastAggregatedTime(),
                    $totalViews
                );
        } else {
            $newAggregatedItem = new CampaignViewsAggregated();
            $newAggregatedItem->setClient($viewsDto->getClientId());
            $newAggregatedItem->setCampaign($viewsDto->getCampaignId());
            $newAggregatedItem->setStep($viewsDto->getStep());
            $newAggregatedItem->setLastAggregationTime(new \DateTime($viewsDto->getLastAggregatedTime()));
            $newAggregatedItem->setTotal($viewsDto->getTotal());
            $this->entityManager->persist($newAggregatedItem);
            $this->entityManager->flush();
        }
    }
}
