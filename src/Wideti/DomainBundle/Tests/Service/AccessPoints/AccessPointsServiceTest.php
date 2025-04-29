<?php

namespace Wideti\DomainBundle\Tests\Service\AccessPoints;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\FrontendBundle\Factory\Nas;

/**
 * Class AccessPointsServiceTest
 * @package Wideti\DomainBundle\Tests\Service\AccessPoints
 */
class AccessPointsServiceTest extends WebTestCase
{
    const EXCEPTION_FAIL_TO_SAVE = "Process fails";
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $accessPointsRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $nas;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var AccessPointsService
     */
    private $accessPointsService;

    public function setUp()
    {
        $this->cacheService = $this->getMockBuilder(CacheServiceImp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessPointsRepository = $this->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->nas = $this->getMockBuilder(Nas::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessPointsService = new AccessPointsService(
            $this->cacheService,
            $this->accessPointsRepository,
            $this->logger
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testMustThrowExceptionIfAccessPointDoesNotExist()
    {
        $this->accessPointsRepository->expects($this->once())
            ->method("getAccessPoint")
            ->willReturn(null);

        $this->accessPointsService->verifyAccessPoint($this->nas, $this->client);
    }

    public function testMustNotSaveAndReturnFalseIfAccessPointIsVerified()
    {
        $accessPoint = new AccessPoints();
        $accessPoint->setRequestVerified(true);

        $this->accessPointsRepository->expects($this->once())
            ->method("getAccessPoint")
            ->willReturn($accessPoint);

        $result = $this->accessPointsService->verifyAccessPoint($this->nas, $this->client);

        $this->assertThat($result, new \PHPUnit_Framework_Constraint_IsFalse());
    }

    public function testMustThrowExceptionIfSavingProcessFails()
    {
        $accessPoint = new AccessPoints();
        $accessPoint->setRequestVerified(false);

        $this->accessPointsRepository->expects($this->once())
            ->method("getAccessPoint")
            ->willReturn($accessPoint);

        $this->accessPointsRepository->expects($this->once())
            ->method("save")
            ->will($this->throwException(new \Exception("Process fails")));

        $result = $this->accessPointsService->verifyAccessPoint($this->nas, $this->client);

        $this->assertEquals(self::EXCEPTION_FAIL_TO_SAVE, $result);
    }
}