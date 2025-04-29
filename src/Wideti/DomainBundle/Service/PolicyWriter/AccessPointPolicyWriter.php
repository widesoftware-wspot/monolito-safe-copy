<?php

namespace Wideti\DomainBundle\Service\PolicyWriter;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Exception\EmptyRouterModeException;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceImp;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class AccessPointPolicyWriter implements PolicyWriter
{
    use LoggerAware;
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	/**
	 * @var ConfigurationServiceImp
	 */
	private $configurationService;
    /**
     * @var VendorService $vendorService
     */
	private $vendorService;

	/**
	 * AccessPointPolicyWriter constructor.
	 * @param EntityManager $entityManager
	 * @param ConfigurationServiceImp $configurationService
     * @param VendorService $vendorService
	 */
	public function __construct(EntityManager $entityManager,
                                ConfigurationServiceImp $configurationService,
                                VendorService $vendorService)
	{
		$this->entityManager = $entityManager;
		$this->configurationService = $configurationService;
		$this->vendorService = $vendorService;
	}

	/**
	 * @param Nas $nas
	 * @param Guest $guest
	 * @param Client $client
	 * @param RadiusPolicyBuilder $builder
	 * @return void
	 */
	public function write(Nas $nas, Guest $guest, Client $client, RadiusPolicyBuilder $builder)
	{
		$apName     = $this->getAccessPointName($nas, $client);
		$routerMode = $this->getRouterMode($nas, $client);
		$timezone   = $this->getAccessPointTimezone($nas, $client);

		$builder->withAccessPointPolicy(
			$apName,
			$nas->getAccessPointMacAddress(),
			$nas->getGuestDeviceMacAddress(),
			$nas->getVendorName(),
			$routerMode,
			$timezone
		);
	}

	/**
	 * @param Nas $nas
	 * @param Client $client
	 * @return string
	 */
	private function getAccessPointName(Nas $nas, Client $client)
	{
		$ap = $this
			->entityManager
			->getRepository('DomainBundle:AccessPoints')
			->findOneBy([
				'client' => $client,
				'identifier' => $nas->getAccessPointMacAddress()
			]);

		return !empty($ap) ? $ap->getFriendlyName() : $nas->getAccessPointMacAddress();
	}


	/**
	 * @param Nas $nas
	 * @return mixed|string
     * @throws EmptyRouterModeException
	 */
	private function getRouterMode(Nas $nas, Client $client)
	{
        $vendor = $this->vendorService->getVendorByCalledStationIdAndClient($nas->getAccessPointMacAddress(), $client);
        if (!$vendor || empty($vendor->getRouterMode())) {
            $this->logger->addWarning("Router mode not found for client:".$client->getDomain());
            return Vendor::DEFAULT_ROUTERMODE;
        }

		return $vendor->getRouterMode();
	}

	private function getAccessPointTimezone(Nas $nas, Client $client)
    {
        $ap = $this
            ->entityManager
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
               'client' => $client,
               'identifier' => $nas->getAccessPointMacAddress()
            ]);

        return !empty($ap) ? $ap->getTimezone() : TimezoneService::DEFAULT_TIMEZONE;
    }
}
