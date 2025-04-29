<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\SmsBillingControlRepository;

class LastBillingDateService
{
	/**
	 * @var SmsBillingControlRepository
	 */
	private $smsBillingControlRepository;

	/**
	 * DateIntervalManagementService constructor.
	 * @param SmsBillingControlRepository $smsBillingControlRepository
	 */
	public function __construct(SmsBillingControlRepository $smsBillingControlRepository)
	{
		$this->smsBillingControlRepository = $smsBillingControlRepository;
	}

	/**
	 * @param Client $client
	 * @return \DateTime|null
	 * @throws \Exception
	 */
	public function getLastBillingDateSent(Client $client)
	{
		$lastBilling = $this->smsBillingControlRepository->getLastSendingDate($client);

		if ($lastBilling) return $lastBilling->modify('+1 day');

		$firstSmsSentDate = $this->smsBillingControlRepository->getFirstSmsSentDate($client);

		return $firstSmsSentDate ?: (new \DateTime(date("Y-m-{$client->getClosingDate()}")))->modify('-1 month');
	}
}
