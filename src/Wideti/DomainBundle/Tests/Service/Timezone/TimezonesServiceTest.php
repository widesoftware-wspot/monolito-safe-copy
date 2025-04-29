<?php

namespace Wideti\DomainBundle\Tests\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Entity\Zone;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Tests\WspotTestCase;

class TimezonesServiceTest extends WspotTestCase
{
    /**
     * @var TimezoneService
     */
    private $timezoneService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AccessPointsService
     */
    private $accessPointService;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        self::bootKernel();
        $this->container = static::$kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->accessPointService = new AccessPointsService();
        $this->timezoneService = new TimezoneService($this->entityManager, $this->accessPointService);
    }

    public function testMustReturnBrazilianTimezones()
    {
        $brTimezones = $this->timezoneService->getAllBrazilianTimezones();
        $brTimezonesAbbrExpected = ["America/Araguaina","America/Bahia","America/Belem","America/Boa_Vista","America/Campo_Grande", "America/Cuiaba", "America/Eirunepe", "America/Fortaleza", "America/Maceio", "America/Manaus", "America/Noronha", "America/Porto_Velho", "America/Recife", "America/Rio_Branco", "America/Santarem", "America/Sao_Paulo"];
        $brTimezonesArray = [];
        foreach ($brTimezones as $brTimezone) {
            array_push($brTimezonesArray, $brTimezone->getZoneName());
        }
        $this->assertArraySubset($brTimezonesArray,$brTimezonesAbbrExpected);
    }

    public function testMustReturnAllTimezonesExceptBrazillian()
    {
        $allTimezonesExceptBrazillian = $this->timezoneService->getAllTimezonesExceptBrazilian();
        $allTimezonesExceptBrazillianArray = [];
        foreach ($allTimezonesExceptBrazillian as $timezone) {
            array_push($allTimezonesExceptBrazillianArray, $timezone->getZoneName());
        }
        $arraySize = sizeof($allTimezonesExceptBrazillianArray);
        $allTimezonesExceptBrazillianSize = 408;
        $this->assertEquals($allTimezonesExceptBrazillianSize, $arraySize);
    }

    public function testMustReturnAllTimezones()
    {
        $allTimezones = $this->timezoneService->getAllTimezones();
        $isTimezoneObject = ($allTimezones[0] instanceof Zone);
        $this->assertTrue($isTimezoneObject);
    }
}
