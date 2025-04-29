<?php


namespace Wideti\DomainBundle\Service\AccessPointsGroupsConfiguration;


use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use http\Exception\BadMethodCallException;
use Monolog\Logger;
use mysql_xdevapi\Exception;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\ClientConfiguration;
use Wideti\DomainBundle\Entity\Configuration;
use Wideti\DomainBundle\Exception\AccessPointsGroupsConfigurationsException;
use Wideti\DomainBundle\Repository\ConfigurationRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\AccessPointsGroupsConfiguration\Helpers\ConfigurationConstructor;

class AccessPointsGroupsConfigurationServiceImp implements AccessPointsGroupsConfigurationService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;
    /**
     * @var Monlog\Logger $logger
     */
    private $logger;
    /**
     * @var ConfigurationRepository $configurationRepository
     */
    private $configurationRepository;

    /**
     * AccessPointsGroupsConfigurationServiceImp constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(EntityManager $entityManager,
                                Logger $logger,
                                ConfigurationRepository $configurationRepository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->configurationRepository = $configurationRepository;
    }


    /**
     * @param AccessPointsGroups $accessPointsGroup
     * @param array $configurations
     * @param Client $client
     * @throws AccessPointsGroupsConfigurationsException
     */
    public function persistAccessPointsGroupsConfigurations($accessPointsGroup,
                                                            $configurations,
                                                            $client)
    {
        if (!$accessPointsGroup || !$configurations || !$client) {
            $this->logger->addCritical("O valor de accessPoint é ".$accessPointsGroup."e o das configurações é".$configurations);
            throw new AccessPointsGroupsConfigurationsException();
        }

        $this->entityManager->persist($accessPointsGroup);

        $isDefault  = $configurations['isDefault'] ? 1 : 0;

        $allConf = $this->configurationRepository->findAllConfigurations();

        foreach ($configurations['items'] as $configuration) {
            $accessPointGroupConfig = $this->handleConfiguration($configuration,
                $accessPointsGroup,
                $client,
                $isDefault,
                $allConf);

            if (!$accessPointGroupConfig) {
                $this->entityManager->close();
                //Avoiding memory leaks
                $this->entityManager = null;
                throw new AccessPointsGroupsConfigurationsException("A configuração do grupo de acesso é vazia");
            }

            $this->entityManager->persist($accessPointGroupConfig);
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->entityManager->close();
            //Avoiding memory leaks
            $this->entityManager = null;
            $this->logger->
            addCritical("Falha na persistencia das entidades das configurações dos grupos de pontos de acesso. Com a mensagem: ". $e->getMessage());
        }
    }

    /**
     * @param array $configuration
     * @param AccessPointsGroups $accessPointGroup
     * @param Client $client
     * @param int $isDefault
     * @param array $allConf
     * @return ClientConfiguration|null
     */
    public function handleConfiguration($configuration, $accessPointGroup, $client, $isDefault, $allConf)
    {
        if(!$client) {
            return null;
        }

        if (!$accessPointGroup) {
            return null;
        }

        if (empty($configuration)) {
            return null;
        }

        if (empty($allConf)) {
            return null;
        }

        $config    = array_filter($allConf, function($element) use ($configuration){
            if ($element->getKey() == $configuration['key']) {
                return $element;
            }
        });

        if (isset($config)) {
            $cliConfig = new ClientConfiguration();
            $cliConfig->setIsDefault($isDefault);
            $cliConfig->setValue($configuration['value']);
            $cliConfig->setAccessPointGroup($accessPointGroup);
            $cliConfig->setConfiguration(end($config));
            $cliConfig->setClient($client);

            return $cliConfig;
        }

        return null;
    }
}