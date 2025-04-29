<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientConfiguration;
use Wideti\DomainBundle\Entity\Configuration;
use Wideti\DomainBundle\Service\Configuration\Dto\ConfigurationDto;

class ConfigurationRepository extends EntityRepository
{
	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getGroups()
	{
		$query      = "SELECT * FROM configurations GROUP BY group_short_code";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		return $statement->fetchAll();
	}

	/**
	 * @param $groupId
	 * @return array|null
	 */
	public function getByGroupId($groupId)
	{
		$query = $this->createQueryBuilder('c')
			->select('c')
			->where('c.accessPointGroup = :apGroup')
			->setParameter('apGroup', $groupId)
			->getQuery();

		$result = $query->getResult();

		if (!$result) return null;

		$configs = [];

		/**
		 * @var ClientConfiguration $item
		 */
		foreach ($result as $item) {
			array_push($configs, $this->mapToDto($item->getConfiguration(), $item));
		}

		return $configs;
	}

	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByIdAndGroups()
	{
		return $this->getGroups();
	}

	/**
	 * @param Client $client
	 * @param $accessPointGroupId
	 * @param $key
	 * @return |null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByKeyAndGroups(Client $client, $accessPointGroupId, $key)
	{
		$clientId   = $client->getId();
		$query      = "SELECT * FROM client_configurations cc INNER JOIN configurations c ON cc.configuration_id = c.id WHERE cc.client_id = $clientId AND cc.access_points_group_id = $accessPointGroupId AND c.key = \"$key\"";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		$result = $statement->fetchAll();
		return $result ? $result[0] : null;
	}

    /**
     * @param Client $client
     * @param $key
     * @return |null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDefaultConfigByKey(Client $client, $key)
    {
        $clientId   = $client->getId();
        $query      = "SELECT * FROM client_configurations cc INNER JOIN configurations c ON cc.configuration_id = c.id WHERE cc.client_id = $clientId AND c.key = \"$key\" AND cc.is_default = 1";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result ? $result[0] : null;
    }

	/**
	 * @param $key
	 * @return Configuration|null
	 */
	public function findConfigByKey($key)
	{
        $result = $this->getEntityManager()
            ->getRepository("DomainBundle:Configuration")
            ->findOneBy(["key"=>$key]);
		return $result ? $result : null;
	}

	/**
	 * @param Configuration $configuration
	 * @param ClientConfiguration $configItem
	 * @return ConfigurationDto
	 */
	public function mapToDto($configuration, $configItem)
	{
		$config = new ConfigurationDto();

		$config->setId($configItem->getId());
		$config->setGroupShortCode($configuration->getGroupShortCode());
		$config->setGroupName($configuration->getGroupName());
		$config->setKey($configuration->getKey());
		$config->setLabel($configuration->getLabel());
		$config->setType($configuration->getType());
		$config->setParams($configuration->getParams());
		$config->setValue($configItem->getValue());

		return $config;
	}

    /**
     * @param ClientConfiguration $item
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($item)
    {
        $entityManager = $this->getEntityManager();
        $existingConfig = $entityManager->getRepository(ClientConfiguration::class)->findOneBy([
            'id' => $item->getId()
        ]);
        if ($existingConfig) {
            $existingConfig->setValue($item->getValue());
            return true;
        }
        return false;
    }

    public function updateByKey(Client $client, $key, $value)
    {

        $entityManager = $this->getEntityManager();
        $configuration = $entityManager->getRepository(Configuration::class)->findOneBy([
            'key' => $key
        ]);
        if ($configuration) {
            $clientConfiguration = $entityManager->getRepository(ClientConfiguration::class)->findOneBy([
                'client' => $client,
                'configuration' => $configuration
            ]);

            $clientConfiguration->setValue($value);
            $entityManager->flush();
        }
    }

	/**
	 * @param array $configuration
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function insert(array $configuration)
	{
		$clientId           = $configuration['client'];
		$isDefault          = $configuration['isDefault'] ? 1 : 0;
		$accessPointGroupId = $configuration['accessPointGroup'];

		foreach ($configuration['items'] as $item) {
			$config             = $this->findConfigByKey($item['key']);
			$configurationId    = $config['id'];
			$value              = str_replace('"', '\"', $item['value']);
			$query      = "INSERT INTO client_configurations (configuration_id, client_id, access_points_group_id, is_default, `value`) VALUES ($configurationId, $clientId, $accessPointGroupId, $isDefault, \"$value\")";
			$connection = $this->getEntityManager()->getConnection();
			$statement  = $connection->prepare($query);
			$statement->execute();
		}
	}

    public function removeAllButDefaults(Client $client)
    {
        $entityManager = $this->getEntityManager();

        $clientConfigurations = $entityManager->getRepository(ClientConfiguration::class)->findOneBy([
            'client' => $client,
            'isDefault' => false,
        ]);

        foreach ($clientConfigurations as $configuration) {
            $entityManager->remove($configuration);
        }
        $entityManager->flush();
    }

    public function removeByGroup(Client $client, $groupId)
    {
        $entityManager = $this->getEntityManager();
        $clientConfigurations = $entityManager->getRepository(ClientConfiguration::class)->findBy([
            'client' => $client,
            'accessPointGroup' => $groupId,
        ]);

        foreach ($clientConfigurations as $configuration) {
            $entityManager->remove($configuration);
        }

        $entityManager->flush();
    }

    /**
     * @param $configuration
     * @param AccessPointsGroups $accessPointGroup
     * @param Client $client
     */
	public function persistAccessPointConfiguration ($configuration, $accessPointGroup, $client)
    {
        $isDefault  = $configuration['isDefault'] ? 1 : 0;

        foreach ($configuration['items'] as $item) {

            $config    = $this->findConfigByKey($item['key']);

            $cliConfig = new ClientConfiguration();
            $cliConfig->setIsDefault($isDefault);
            $cliConfig->setValue($item['value']);
            $cliConfig->setAccessPointGroup($accessPointGroup);
            $cliConfig->setConfiguration($config);
            $cliConfig->setClient($client);

            $this->getEntityManager()->persist($cliConfig);
        }
    }

    public function findAllConfigurations ()
    {
        return $this->getEntityManager()->getRepository("DomainBundle:Configuration")
            ->findAll();
    }
}
