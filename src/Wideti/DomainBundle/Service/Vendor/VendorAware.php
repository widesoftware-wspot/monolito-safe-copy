<?php

namespace Wideti\DomainBundle\Service\Vendor;

use Wideti\DomainBundle\Service\Vendor\VendorService;

/**
 *
 * Usage: - [ setVendor, ["@core.service.vendor"] ]
 */
trait VendorAware
{
    /**
     * @var VendorService
     */
    protected $vendor;

    public function setVendor(VendorService $service)
    {
        $this->vendor = $service;
    }
}
