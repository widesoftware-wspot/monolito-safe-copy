<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\SMSBillingControlRepository;

class BillingManager
{
    /**
     * @var SMSBillingControlRepository
     */
    private $SMSBillingControlRepository;
    /**
     * @var BillingExistsService
     */
    private $billingExistsService;
    /**
     * @var CreateBillingService
     */
    private $createBillingService;
	/**
	 * @var DateIntervalManagementService
	 */
	private $dateIntervalManagementService;

	/**
	 * BillingManager constructor.
	 * @param DateIntervalManagementService $dateIntervalManagementService
	 * @param SMSBillingControlRepository $SMSBillingControlRepository
	 * @param BillingExistsService $billingExistsService
	 * @param CreateBillingService $createBillingService
	 */
    public function __construct
    (
    	DateIntervalManagementService $dateIntervalManagementService,
        SMSBillingControlRepository $SMSBillingControlRepository,
        BillingExistsService        $billingExistsService,
        CreateBillingService        $createBillingService
    )
    {
	    $this->dateIntervalManagementService = $dateIntervalManagementService;
	    $this->SMSBillingControlRepository   = $SMSBillingControlRepository;
	    $this->billingExistsService          = $billingExistsService;
	    $this->createBillingService          = $createBillingService;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function manageBilling()
    {
        $clients = $this->getClients();

        if ($clients) {
            $data = [];

            foreach ($clients as $client) {
            	if (!$client->getClosingDate()) continue;

            	$this->dateIntervalManagementService->get($client);

	            $closingDateStart   = $this->dateIntervalManagementService->getClosingDateStart();
	            $closingDateEnd     = $this->dateIntervalManagementService->getClosingDateEnd();

	            if ($closingDateEnd >= date('Y-m-d')) continue;

                $sms = $this->getSMSData($client, $closingDateStart, $closingDateEnd);
                $this->createBilling($client, $closingDateStart, $closingDateEnd, $sms);

                $smsData = $this->billingHistoric($client, $closingDateStart, $closingDateEnd);

                if ($smsData) {
                    $data[] = $smsData;
                }
            }
            return $data;
        }
    }

    /**
     * @return array|\Wideti\DomainBundle\Entity\Client[]
     */
    private function getClients()
    {
        return $this->SMSBillingControlRepository->getClientsForBilling();
    }

    /**
     * @param $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getSMSData(Client $client, $closingDateStart, $closingDateEnd)
    {
        return $this
            ->SMSBillingControlRepository
            ->getSMSData($client, $closingDateStart, $closingDateEnd);
    }

    /**
     * @param $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @param $sms
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createBilling(Client $client, $closingDateStart, $closingDateEnd, $sms)
    {
        $this->createBillingService->create($client, $closingDateStart, $closingDateEnd, $sms);
    }

    /**
     * @param $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function billingHistoric(Client $client, $closingDateStart, $closingDateEnd)
    {
        return $this
            ->SMSBillingControlRepository
            ->getBillingHistoric($client, $closingDateStart, $closingDateEnd);
    }
}
