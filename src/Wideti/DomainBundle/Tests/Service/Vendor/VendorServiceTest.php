<?php

namespace Wideti\DomainBundle\Tests\Service\Vendor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Service\Vendor\VendorService;

class VendorServiceTest extends WebTestCase
{

    public function testMustGetVendorsNameAsList()
    {
        $vendorService = $this
            ->getMockBuilder('Wideti\DomainBundle\Service\Vendor\VendorService')
            ->disableOriginalConstructor()
            ->setMethods(['getVendors'])
            ->getMock();

        $vendorService
            ->expects($this->once())
            ->method('getVendors')
            ->will($this->returnValue(VendorTestHelper::getVendorArray()));

        /** @var VendorService $vendorService */
        $vendorList = $vendorService->getVendorsAsList();

        $this->assertNotEmpty($vendorList);
        $this->assertContainsOnly('string', $vendorList);
        $this->assertArraySubset([
            'Aerohive',
            'Aruba',
            'Cisco',
            'Mikrotik',
            'PfSense',
            'Ruckus-Controlador',
            'Ruckus-Standalone',
            'ZyXEL'], $vendorList);
    }

    public function testMustGetVendorsNameAsListInLowerCase()
    {
        $vendorService = $this
            ->getMockBuilder('Wideti\DomainBundle\Service\Vendor\VendorService')
            ->disableOriginalConstructor()
            ->setMethods(['getVendors'])
            ->getMock();

        $vendorService
            ->expects($this->once())
            ->method('getVendors')
            ->will($this->returnValue(VendorTestHelper::getVendorArray()));

        /** @var VendorService $vendorService */
        $vendorList = $vendorService->getVendorsAsList(true);

        $this->assertNotEmpty($vendorList);
        $this->assertContainsOnly('string', $vendorList);
        $this->assertArraySubset([
            'aerohive',
            'aruba',
            'cisco',
            'mikrotik',
            'pfsense',
            'ruckus-controlador',
            'ruckus-standalone',
            'zyxel'], $vendorList);
    }
}
