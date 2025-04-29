<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="client_configurations")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ConfigurationRepository")
 */
class ClientConfiguration
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Configuration")
	 * @ORM\JoinColumn(name="configuration_id", referencedColumnName="id")
	 */
	protected $configuration;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Client")
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 */
	protected $client;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\AccessPointsGroups")
	 * @ORM\JoinColumn(name="access_points_group_id", referencedColumnName="id")
	 */
	protected $accessPointGroup;

	/**
	 * @ORM\Column(name="is_default", type="boolean")
	 */
	private $isDefault;

	/**
	 * @ORM\Column(name="`value`", type="text")
	 */
	private $value;

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getIsDefault()
	{
		return $this->isDefault;
	}

	/**
	 * @param mixed $isDefault
	 */
	public function setIsDefault($isDefault)
	{
		$this->isDefault = $isDefault;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}	/**
	 * @return mixed
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param mixed $accessPointGroup
	 */
	public function setAccessPointGroup($accessPointGroup)
	{
		$this->accessPointGroup = $accessPointGroup;
	}

	/**
	 * @return mixed
	 */
	public function getAccessPointGroup()
	{
		return $this->accessPointGroup;
	}

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }



}
