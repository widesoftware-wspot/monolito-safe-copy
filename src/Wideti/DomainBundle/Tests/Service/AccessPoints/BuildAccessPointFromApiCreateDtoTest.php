<?php

namespace Wideti\DomainBundle\Tests\Service\AccessPoints;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\TemplateRepository;
use Wideti\DomainBundle\Service\AccessPoints\BuildAccessPointFromApiCreateDtoServiceImp;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;

class BuildAccessPointFromApiCreateDtoTest extends WebTestCase
{
    public function testMustCreateEntityIfDtoIsCorrect()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 1]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 1]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $dto = new CreateAccessPointDto();
        $dto
            ->setAction($dto::ACTION_CREATE)
            ->setIdentifier('11-11-11-11-11-11')
            ->setLocal("cozinha")
            ->setFriendlyName("Minha Ap")
            ->setVendor('cisco')
            ->setGroupId(1)
            ->setTemplateId(1)
            ->setClient(Client::createClientWithId(1))
            ->setStatus(1);

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertEquals("11-11-11-11-11-11", $entity->getIdentifier());
        $this->assertEquals("cozinha", $entity->getLocal());
        $this->assertEquals("Minha Ap", $entity->getFriendlyName());
        $this->assertEquals("cisco", $entity->getVendor());
        $this->assertEquals("Grupo padrão", $entity->getGroup()->getGroupName());
        $this->assertEquals("Template padrão", $entity->getTemplate()->getName());
        $this->assertEquals(1, $entity->getStatus());
        $this->assertEquals(1, $entity->getClient()->getId());
    }

    public function testMustCreateEntityWithTemplateNullIfTemplateIdIsNull()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 1]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 0]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $dto = new CreateAccessPointDto();
        $dto
            ->setAction($dto::ACTION_CREATE)
            ->setIdentifier('11-11-11-11-11-11')
            ->setLocal("cozinha")
            ->setFriendlyName("Minha Ap")
            ->setVendor('cisco')
            ->setGroupId(1)
            ->setTemplateId(null)
            ->setClient(Client::createClientWithId(1))
            ->setStatus(1);

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertEquals("11-11-11-11-11-11", $entity->getIdentifier());
        $this->assertEquals("cozinha", $entity->getLocal());
        $this->assertEquals("Minha Ap", $entity->getFriendlyName());
        $this->assertEquals("cisco", $entity->getVendor());
        $this->assertEquals("Grupo padrão", $entity->getGroup()->getGroupName());
        $this->assertNull($entity->getTemplate());
        $this->assertEquals(1, $entity->getStatus());
        $this->assertEquals(1, $entity->getClient()->getId());
    }

    public function testMustCreateEntityWithTemplateNullIfTemplateIdIsNotInt()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 0]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 0]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $dto = new CreateAccessPointDto();
        $dto
            ->setAction($dto::ACTION_CREATE)
            ->setTemplateId("12");

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertNull($entity->getTemplate());
    }

    public function testMustCreateEntityWithGroupNullIfGroupIdIsNull()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 0]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 0]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $dto = new CreateAccessPointDto();
        $dto
            ->setAction($dto::ACTION_CREATE)
            ->setIdentifier('11-11-11-11-11-11')
            ->setLocal("cozinha")
            ->setFriendlyName("Minha Ap")
            ->setVendor('cisco')
            ->setGroupId(null)
            ->setTemplateId(null)
            ->setStatus(1);

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertEquals("11-11-11-11-11-11", $entity->getIdentifier());
        $this->assertEquals("cozinha", $entity->getLocal());
        $this->assertEquals("Minha Ap", $entity->getFriendlyName());
        $this->assertEquals("cisco", $entity->getVendor());
        $this->assertNull($entity->getGroup());
        $this->assertNull($entity->getTemplate());
        $this->assertEquals(1, $entity->getStatus());
    }

    public function testMustCreateEntityWithGroupNullIfGroupIdIsNotInt()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 0]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 0]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $dto = new CreateAccessPointDto();
        $dto
            ->setAction($dto::ACTION_CREATE)
            ->setGroupId("1");

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertNull($entity->getGroup());
    }

    public function testMustCreateEmptyEntityIfDtoIsEmpty()
    {
        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 0]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 0]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 0]);

        $client = Client::createClientWithId(1);
        $dto = CreateAccessPointDto::createFromAssocArray([], $client);

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($dto);

        $this->assertNull($entity->getIdentifier());
        $this->assertNull($entity->getLocal());
        $this->assertNull($entity->getFriendlyName());
        $this->assertNull($entity->getVendor());
        $this->assertNull($entity->getGroup());
        $this->assertNull($entity->getTemplate());
        $this->assertNull($entity->getStatus());
        $this->assertInstanceOf(Client::class, $entity->getClient());
        $this->assertEquals(1, $entity->getClient()->getId());
    }

    public function testCreateAccessPointUpdateEntity()
    {
        $jsonString = '
            {
                "id" : 1,
                "friendlyName": "Tilápia",
                "local": "Campinas City",
                "status": 1,
                "templateId": 1,
                "groupId" : 1
            }
        ';

        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);

        $apGroupRepository = $this->getAccessPointsGroupsMock(['findOneBy' => 1]);
        $templateRepository = $this->getTemplateRepositoryMock(['findOneBy' => 1]);
        $accessPointRepository = $this->getAccessPointsMock(['findOneBy' => 1]);

        $service = new BuildAccessPointFromApiCreateDtoServiceImp($templateRepository, $apGroupRepository, $accessPointRepository);
        $entity = $service->getEntity($apDto);

        $this->assertEquals("Tilápia", $entity->getFriendlyName());
        $this->assertEquals("Campinas City", $entity->getLocal());
        $this->assertEquals(1, $entity->getStatus());
        $this->assertNotNull($entity->getTemplate());
        $this->assertNotNull($entity->getGroup());
    }

    /**
     * @param array $timesExecute
     * @return TemplateRepository
     */
    public function getTemplateRepositoryMock(array $timesExecute)
    {
        $template = new Template();
        $template->setName("Template padrão");

        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\TemplateRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute['findOneBy']))
            ->method('findOneBy')
            ->will($this->returnValue($template));

        /** @var TemplateRepository $mock */
        return $mock;
    }

    /**
     * @param array $timesExecute
     * @return AccessPointsGroupsRepository
     */
    public function getAccessPointsGroupsMock(array $timesExecute)
    {
        $group = new AccessPointsGroups();
        $group->setGroupName("Grupo padrão");

        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\AccessPointsGroupsRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute['findOneBy']))
            ->method('findOneBy')
            ->will($this->returnValue($group));

        /** @var AccessPointsGroupsRepository $mock */
        return $mock;
    }

    /**
     * @param array $timesExecute
     * @return AccessPointsRepository
     */
    public function getAccessPointsMock(array $timesExecute)
    {
        $accessPoints = new AccessPoints();
        $accessPoints->setFriendlyName("Nome Amigo");

        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\AccessPointsRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute['findOneBy']))
            ->method('findOneBy')
            ->will($this->returnValue($accessPoints));

        /** @var AccessPointsRepository $mock */
        return $mock;
    }
}
