<?php


namespace Wideti\DomainBundle\Tests\Service\AccessPointsGroupsConfigurations;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use phpDocumentor\Reflection\Types\This;
use PHPUnit\Framework\TestCase;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientConfiguration;
use Wideti\DomainBundle\Repository\ConfigurationRepository;
use Wideti\DomainBundle\Service\AccessPointsGroupsConfiguration\AccessPointsGroupsConfigurationServiceImp;
use Wideti\DomainBundle\Exception\AccessPointsGroupsConfigurationsException;
use Wideti\DomainBundle\Entity\Configuration;

class AccessPointsGroupsConfigurationsTest extends TestCase
{
    /**
     * @var AccessPointsGroupsConfigurationServiceImp $accessPointsGroupsConfigurationsServiceImp
     */
    protected $accessPointsGroupsConfigurationsServiceImp;
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var array
     */
    private $configuration;
    /**
     * @var array
     */
    private $allConfig;

    protected function setUp()
    {
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $configurationRepository = $this->getMockBuilder(ConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMockClassName('')
            ->getMock();

        $this->accessPointsGroupsConfigurationsServiceImp =
            new AccessPointsGroupsConfigurationServiceImp($entityManager, $logger, $configurationRepository);

        $this->configuration = array("isDefault" => 1,
            "items"=> array (
                'groupShortCode' => 'default',
                'groupName' => 'Default',
                'type' => 'text',
                'label' => 'Nome da Empresa',
                'params' =>
                    array (
                        'constraints' =>
                            array (
                                'not_blank' =>
                                    array (
                                        'message' => 'O preenchimento deste campo é obrigatório.',
                                    ),
                            ),
                    ),
                'key' => 'partner_name',
                'value' => 'Empresa',
            )
        );

        $config = new Configuration();
        $config->setGroupName("Teste1");
        $config->setParams(['param'=>"Teste"]);
        $config->setLabel("Label de Teste");
        $config->setGroupShortCode("Test Short Code");
        $config->setType("Type Teste");
        $config->setKey("Teste Key");
        $config->setId(1);
        $this->allConfig = [ $config ];

    }

    public function testPersistenceMustFailWithNullGroups ()
    {
        $this->expectException(AccessPointsGroupsConfigurationsException::class);
        $this->accessPointsGroupsConfigurationsServiceImp->persistAccessPointsGroupsConfigurations(null,null, null);
    }

    public function testPersistenceMustThrowExceptionWithEmptyAccessPointsGroupConfig()
    {
        $this->expectException(AccessPointsGroupsConfigurationsException::class);
        $this->accessPointsGroupsConfigurationsServiceImp
            ->persistAccessPointsGroupsConfigurations(new AccessPointsGroups(),
            $this->configuration,
            $this->client);
    }

    public function testHandlerConfigurationMustReturnWithNullClient()
    {
        //Cenário
        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setClient($this->client);
        $accessPointGroup->setIsDefault(0);
        $accessPointGroup->setGroupName("Teste");
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setCreated(new \DateTime());
        $accessPointGroup->setIsMaster(0);
        $accessPointGroup->setParent(null);
        $accessPointGroup->setParentTemplate(null);
        $accessPointGroup->setUpdated(null);

        //Ação
        $result = $this->accessPointsGroupsConfigurationsServiceImp->handleConfiguration($this->configuration,
            $accessPointGroup,
            null,
            1,
            $this->allConfig);

        //Asserts
        $this->assertNull($result);
    }

    public function testHanderConfiguratuionMustReturnNullWithEmptyConfigs ()
    {
        //Cenário
        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setClient($this->client);
        $accessPointGroup->setIsDefault(0);
        $accessPointGroup->setGroupName("Teste");
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setCreated(new \DateTime());
        $accessPointGroup->setIsMaster(0);
        $accessPointGroup->setParent(null);
        $accessPointGroup->setParentTemplate(null);
        $accessPointGroup->setUpdated(null);

        //Ação
        $result = $this->accessPointsGroupsConfigurationsServiceImp->handleConfiguration([],
            $accessPointGroup,
            $this->client,
            1,
            $this->allConfig);

        //Asserts
        $this->assertNull($result);
    }

    public function testHanderConfiguratuionMustReturnNullWithEmptyAllConfigs ()
    {
        //Cenário
        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setClient($this->client);
        $accessPointGroup->setIsDefault(0);
        $accessPointGroup->setGroupName("Teste");
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setCreated(new \DateTime());
        $accessPointGroup->setIsMaster(0);
        $accessPointGroup->setParent(null);
        $accessPointGroup->setParentTemplate(null);
        $accessPointGroup->setUpdated(null);

        //Ação
        $result = $this->accessPointsGroupsConfigurationsServiceImp->handleConfiguration($this->configuration,
            $accessPointGroup,
            $this->client,
            1,
            []);

        //Asserts
        $this->assertNull($result);
    }


    public function testHanderConfiguratuionMustReturnNullWithNullAllConfigs ()
    {
        //Cenário
        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setClient($this->client);
        $accessPointGroup->setIsDefault(0);
        $accessPointGroup->setGroupName("Teste");
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setAccessPoints(null);
        $accessPointGroup->setCreated(new \DateTime());
        $accessPointGroup->setIsMaster(0);
        $accessPointGroup->setParent(null);
        $accessPointGroup->setParentTemplate(null);
        $accessPointGroup->setUpdated(null);

        //Ação
        $result = $this->accessPointsGroupsConfigurationsServiceImp->handleConfiguration($this->configuration,
            $accessPointGroup,
            $this->client,
            1,
            null);

        //Asserts
        $this->assertNull($result);
    }

    public function testHanderConfiguratuionMustReturnNullWithNullAccessPointGroup ()
    {

        //Ação
        $result = $this->accessPointsGroupsConfigurationsServiceImp->handleConfiguration($this->configuration,
            null,
            $this->client,
            1,
            null);

        //Asserts
        $this->assertNull($result);
    }
}