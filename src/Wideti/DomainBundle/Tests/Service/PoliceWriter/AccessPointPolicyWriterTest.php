<?php


namespace Wideti\DomainBundle\Tests\Service\PoliceWriter;


use Doctrine\ORM\EntityManager;
use phpDocumentor\Reflection\Types\This;
use PHPUnit\Framework\TestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Exception\EmptyRouterModeException;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceImp;
use Wideti\DomainBundle\Service\PolicyWriter\AccessPointPolicyWriter;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\FrontendBundle\Controller\FrontendController;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasImp;

class AccessPointPolicyWriterTest extends TestCase
{
    /**
     * @var VendorService $vendorService
     */
    private $vendorService;
    private $accessPointPolicyWriter;
    private $client;

    protected function setUp()
    {
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $configurationService = $this->getMockBuilder(ConfigurationServiceImp::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $this->vendorService = $this->getMockBuilder(VendorService::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();


        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $this->accessPointPolicyWriter = new AccessPointPolicyWriter(
            $entityManager,
            $configurationService,
            $this->vendorService
        );
    }

    public function testCreateRouterMode()
    {
        //Cenário
        $reflector = new \ReflectionClass(AccessPointPolicyWriter::class);
        $method = $reflector->getMethod('getRouterMode');
        $method->setAccessible(true);

        $nas = new NasImp('F1-5E-01-0D-16-98',
            '2F-DA-02-4B-2A-02',
            'mikrotik',
                        new NasFormPostParameter('GET', '192.168.101.209', '6060', ''),
                        [],
                        []);

        $vendor = new Vendor();
        $vendor->setVendor('mikrotik');
        $vendor->setRouterMode('router');

        $this->vendorService->expects($this->any())
            ->method('getVendorByName')
            ->willReturn($vendor);

        //Ação
        $result = $method->invokeArgs($this->accessPointPolicyWriter, [$nas, $this->client]);

        //Asserções
        $this->assertEquals('router', $result);
    }

    public function testCreatebridgeMode()
    {
        //Cenário
        $reflector = new \ReflectionClass(AccessPointPolicyWriter::class);
        $method = $reflector->getMethod('getRouterMode');
        $method->setAccessible(true);

        $nas = new NasImp('F1-5E-01-0D-16-98',
            '2F-DA-02-4B-2A-02',
            'mikrotik',
            new NasFormPostParameter('GET', '192.168.101.209', '6060', ''),
            [],
            []);

        $vendor = new Vendor();
        $vendor->setVendor('mikrotik');
        $vendor->setRouterMode('bridge');

        $this->vendorService->expects($this->any())
            ->method('getVendorByName')
            ->willReturn($vendor);

        //Ação
        $result = $method->invokeArgs($this->accessPointPolicyWriter, [$nas, $this->client]);

        //Asserções
        $this->assertEquals('bridge', $result);
    }

    public function testReturnExceptionWhenVendorNull()
    {
        //Cenário
        $reflector = new \ReflectionClass(AccessPointPolicyWriter::class);
        $method = $reflector->getMethod('getRouterMode');
        $method->setAccessible(true);

        $nas = new NasImp('F1-5E-01-0D-16-98',
            '2F-DA-02-4B-2A-02',
            'mikrotik',
            new NasFormPostParameter('GET', '192.168.101.209', '6060', ''),
            [],
            []);

        $vendor = new Vendor();
        $vendor->setVendor('mikrotik');
        $vendor->setRouterMode('router');

        $this->vendorService->expects($this->any())
            ->method('getVendorByName')
            ->willReturn(null);

        //Ação
        $this->expectException(EmptyRouterModeException::class);
        $method->invokeArgs($this->accessPointPolicyWriter, [$nas, $this->client]);
    }

}
