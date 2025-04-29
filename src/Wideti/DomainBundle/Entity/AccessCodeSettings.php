<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;

/**
 * @ORM\Table(name="access_code_settings")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessCodeSettingsRepository")
 */
class AccessCodeSettings
{
	use TimestampableEmbed;

    const STATUS_INACTIVE   = 0;
    const STATUS_ACTIVE     = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 *
	 */
	protected $client;

    /**
     * @ORM\Column(name="enable_free_access", type="boolean", options={"default":0} )
     */
    private $enableFreeAccess = 0;

    /**
     * @ORM\Column(name="free_access_time", type="string", length=50, nullable=true)
     */
    private $freeAccessTime;
    /**
     * @ORM\Column(name="free_access_period", type="string", length=50, nullable=true)
     */
    private $freeAccessPeriod;
    /**
     * @ORM\Column(name="end_period_text", type="text", nullable=true)
     */
    private $endPeriodText;

	/**
	 * @ORM\Column(name="in_access_points", type="integer")
	 */
	private $inAccessPoints = 0;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="AccessPoints", inversedBy="accessCodeSettings")
	 * @ORM\JoinTable(name="access_code_settings_access_points",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="access_code_settings_id", referencedColumnName="id")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="access_point_id", referencedColumnName="id")
	 *   }
	 * )
	 * @Exclude()
	 */
	protected $accessPoints;

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
	 * @param Client $client
	 * @return $this
	 */
	public function setClient(\Wideti\DomainBundle\Entity\Client $client)
	{
		$this->client = $client;

		return $this;
	}

    /**
     * @return mixed
     */
    public function isEnableFreeAccess()
    {
        return $this->enableFreeAccess;
    }

    /**
     * @param mixed $enableFreeAccess
     */
    public function setEnableFreeAccess($enableFreeAccess)
    {
        $this->enableFreeAccess = $enableFreeAccess;
    }

    /**
     * @return mixed
     */
    public function getFreeAccessTime()
    {
        return $this->freeAccessTime;
    }

    /**
     * @param mixed $freeAccessTime
     */
    public function setFreeAccessTime($freeAccessTime)
    {
        $this->freeAccessTime = $freeAccessTime;
    }

    /**
     * @return mixed
     */
    public function getFreeAccessPeriod()
    {
        return $this->freeAccessPeriod;
    }

    /**
     * @param mixed $freeAccessPeriod
     */
    public function setFreeAccessPeriod($freeAccessPeriod)
    {
        $this->freeAccessPeriod = $freeAccessPeriod;
    }

    /**
     * @return mixed
     */
    public function getEndPeriodText()
    {
        return $this->endPeriodText;
    }

    /**
     * @param mixed $endPeriodText
     */
    public function setEndPeriodText($endPeriodText)
    {
        $this->endPeriodText = $endPeriodText;
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
	 * @return AccessCodeSettings
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
