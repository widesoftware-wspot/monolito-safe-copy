<?php
namespace Wideti\DomainBundle\Tests\Service\GuestGroupService;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\Group\GroupService;
use Wideti\FrontendBundle\Factory\Nas;

class GuestGroupServiceTest extends WebTestCase
{
    private $nas;
    private $groupService;
    private $accessPoint;
    private $accessPointGroup;
    private $accessPointsRepository;

    public function setUp()
    {
        $mockObjects = [
            'nas'                    => Nas::class,
            'accessPoint'            => AccessPoints::class,
            'accessPointGroup'       => AccessPointsGroups::class,
            'accessPointsRepository' => AccessPointsRepository::class,
            'groupService'           => GroupService::class,
        ];

        foreach ($mockObjects as $classAttribute => $classToMock) {
            $this->createMock($classAttribute, $classToMock);
        }
    }

    public function createMock($classAttribute, $classToMock)
    {
        $this->$classAttribute = $this->getMockBuilder($classToMock)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function prepareMockForTest($getAccessPointMacAddress,
        $getGroupIfExistsFromAccessPoint,
        $getGroupIfExistsFromAccessPointGroup,
        $getGroupFromNAS,
        $getGroupThatMatchesAllAccessPoints)
    {
        $this->nas->expects($this->any())
            ->method('getAccessPointMacAddress')
            ->willReturn($getAccessPointMacAddress);

        $this->groupService->expects($this->any())
            ->method('getGroupIfExistsFromAccessPoint')
            ->willReturn($getGroupIfExistsFromAccessPoint);

        $this->groupService->expects($this->any())
            ->method('getGroupIfExistsFromAccessPointGroup')
            ->willReturn($getGroupIfExistsFromAccessPointGroup);

        $this->groupService->expects($this->any())
            ->method('getGroupFromNAS')
            ->willReturn($getGroupFromNAS);

        $this->groupService->expects($this->any())
            ->method('getGroupThatMatchesAllAccessPoints')
            ->willReturn($getGroupThatMatchesAllAccessPoints);
    }

    public function testWillFindGuestGroupForAccessPoint()
    {
        $this->prepareMockForTest('11-11-11-11-11-11', $this->returnValue(true),
            $this->returnValue(false), $this->returnValue(true), $this->returnValue(false));

        $this->assertEquals($this->returnValue(true), $this->groupService->getGroupIfExistsFromAccessPoint($this->nas));
    }

    public function testWillFindGuestGroupForAccessPointGroup()
    {
        $this->prepareMockForTest('11-11-11-11-11-11', $this->returnValue(false),
            $this->returnValue(true), $this->returnValue(true), $this->returnValue(false));

        $this->assertEquals($this->returnValue(true), $this->groupService->getGroupIfExistsFromAccessPointGroup($this->nas));
    }

    public function testWillFindAGuestGroupThatMatchesAllAccessPoints()
    {
        $this->prepareMockForTest('11-11-11-11-11-11', $this->returnValue(false),
            $this->returnValue(false), $this->returnValue(true), $this->returnValue(true));

        $this->assertEquals($this->returnValue(true), $this->groupService->getGroupThatMatchesAllAccessPoints());
    }

    public function testWillNotFindAnyGuestGroupForAccessPointGroup()
    {
        $this->prepareMockForTest('11-22-33-44-55-66', $this->returnValue(false),
            $this->returnValue(false), $this->returnValue(true), $this->returnValue(false));

        $this->assertEquals($this->returnValue(false), $this->groupService->getGroupIfExistsFromAccessPointGroup($this->nas));
    }
}