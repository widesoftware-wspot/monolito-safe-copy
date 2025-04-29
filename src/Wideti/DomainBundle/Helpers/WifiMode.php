<?php

namespace Wideti\DomainBundle\Helpers;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Exception\EmptyRouterModeException;
use Wideti\DomainBundle\Exception\NasEmptyException;
use Wideti\DomainBundle\Repository\VendorRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\FrontendBundle\Factory\Nas;

class WifiMode
{
    const ROUTER_MODE = 'router';
    const BRIDGE_MODE = 'bridge';
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var VendorService $vendorService
     */
    private $vendorService;

    /**
     * @param ConfigurationService $configurationService
     * @param Session $session
     * @param CacheServiceImp $cacheService
     * @param VendorService $vendorService
     */
    public function __construct(
        ConfigurationService $configurationService,
        Session $session,
        CacheServiceImp $cacheService,
        VendorService $vendorService
    ) {
        $this->configurationService = $configurationService;
        $this->session              = $session;
        $this->cacheService         = $cacheService;
        $this->vendorService        = $vendorService;
    }

    public function getCurrentActiveMode()
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($nas) {
            $vendor = $this->vendorService->getVendorByName($nas->getVendorName());
            if (!$vendor || empty($vendor->getRouterMode())) {
                throw new EmptyRouterModeException();
            }
            return $vendor->getRouterMode();
        }

    }

    public function getDownloadUploadBasedOnVendor($vendor)
    {
        $vendor = $this->vendorService->getVendorByName($vendor);
        if (!$vendor) {
            throw new EmptyRouterModeException();
        }

        return $vendor->getRouterMode();
    }

    public function getDownloadUploadBasedOnVendorAndClient($calledStationId, $client)
    {
        $vendor = $this->vendorService->getVendorByCalledStationIdAndClient($calledStationId, $client);
        if(!$vendor) {
            throw new EmptyRouterModeException();
        }
        return $vendor->getRouterMode();
    }
}
