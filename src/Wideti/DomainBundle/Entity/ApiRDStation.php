<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="api_rdstation")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ApiRDStationRepository")
 */
class ApiRDStation
{
	const INACTIVE = 0;
	const ACTIVE   = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Client")
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 */
	protected $client;

	/**
	 * @ORM\Column(name="title", type="string", length=255, nullable=true)
	 */
	private $title;

	/**
	 * @ORM\Column(name="token", type="string", length=255, nullable=true)
	 */
	private $token;

	/**
	 * @ORM\Column(name="enable_auto_integration", type="boolean", options={"default":0} )
	 */
	private $enableAutoIntegration = self::INACTIVE;

	/**
	 * @ORM\Column(name="in_access_points", type="integer")
	 */
	private $inAccessPoints = 0;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="AccessPoints", inversedBy="apiRDStation")
	 * @ORM\JoinTable(name="api_rdstation_access_points",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="api_rdstation_id", referencedColumnName="id")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="access_point_id", referencedColumnName="id")
	 *   }
	 * )
	 * @Exclude()
	 */
	protected $accessPoints;

	/**
	 * @Gedmo\Timestampable(on="create")
	 * @ORM\Column(name="created", type="datetime", nullable=false)
	 */
	private $created;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->accessPoints = new ArrayCollection();
	}

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
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param mixed $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return mixed
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param mixed $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * @return mixed
	 */
	public function isEnableAutoIntegration()
	{
		return boolval($this->enableAutoIntegration);
	}

	/**
	 * @param mixed $enableAutoIntegration
	 */
	public function setEnableAutoIntegration($enableAutoIntegration)
	{
		$this->enableAutoIntegration = $enableAutoIntegration;
	}

	/**
	 * @return mixed
	 */
	public function getInAccessPoints()
	{
		return $this->inAccessPoints;
	}

	/**
	 * @param mixed $inAccessPoints
	 */
	public function setInAccessPoints($inAccessPoints)
	{
		$this->inAccessPoints = $inAccessPoints;
	}

	/**
	 * Add accessPoints
	 *
	 * @param AccessPoints $accessPoints
	 * @return ApiRDStation
	 */
	public function addAccessPoint(AccessPoints $accessPoints)
	{
		$this->accessPoints[] = $accessPoints;
		return $this;
	}

	/**
	 * Remove accessPoints
	 *
	 * @param AccessPoints $accessPoints
	 */
	public function removeAccessPoint(AccessPoints $accessPoints)
	{
		$this->accessPoints->removeElement($accessPoints);
	}

	/**
	 * Get accessPoints
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getAccessPoints()
	{
		return $this->accessPoints;
	}

	/**
	 * @param AccessPoints[] $accessPoints
	 */
	public function setAccessPoints(array $accessPoints)
	{
		$this->accessPoints = $accessPoints;
	}
}
