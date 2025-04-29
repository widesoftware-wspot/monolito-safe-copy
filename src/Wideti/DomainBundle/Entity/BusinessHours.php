<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;

/**
 * @ORM\Table(name="business_hours")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\BusinessHoursRepository")
 */
class BusinessHours
{
	use TimestampableEmbed;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\BusinessHoursItem", mappedBy="businessHours")
	 */
	private $item;

	/**
	 * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 *
	 */
	protected $client;

	/**
	 * @ORM\Column(name="in_access_points", type="integer")
	 */
	private $inAccessPoints = 0;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="AccessPoints", inversedBy="businessHours")
	 * @ORM\JoinTable(name="business_hours_access_points",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="business_hours_id", referencedColumnName="id")
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
		$this->item = new ArrayCollection();
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
	 * @return BusinessHours
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

	/**
	 * Add item
	 *
	 * @param BusinessHoursItem $item
	 * @return BusinessHours
	 */
	public function addItem(BusinessHoursItem $item)
	{
		$this->item[] = $item;
		return $this;
	}

	/**
	 * Remove item
	 *
	 * @param BusinessHoursItem $item
	 */
	public function removeItem(BusinessHoursItem $item)
	{
		$this->item->removeElement($item);
	}

	/**
	 * @return array|null
	 */
	public function getRawItems()
	{
		return $this->item;
	}

	/**
	 * @return array|null
	 */
	public function getItems()
	{
		if (empty($this->item->getValues())) return null;

		$items = [];

		/**
		 * @var BusinessHoursItem $values
		 */
		foreach ($this->item->getValues() as $values) {
			$dayOfWeek = $values->getDayOfWeek();
    
			if (array_key_exists($dayOfWeek, $items)) {
				// Se a chave já existe, adicione o novo valor ao array existente
				$items[$dayOfWeek][] = [
					'from'  => $values->getStartTime(),
					'to'    => $values->getEndTime()
				];
			} else {
				// Se a chave não existe, crie um novo array com o valor
				$items[$dayOfWeek] = [
					[
						'from'  => $values->getStartTime(),
						'to'    => $values->getEndTime()
					]
				];
			}
		}

		return $items;
	}
}
