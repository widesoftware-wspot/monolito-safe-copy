<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="business_hours_item")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\BusinessHoursItemRepository")
 */
class BusinessHoursItem
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\BusinessHours", inversedBy="item")
	 * @ORM\JoinColumn(name="business_hours_id", referencedColumnName="id")
	 */
	protected $businessHours;

	/**
	 * @ORM\Column(name="day_of_week", type="string", length=55)
	 */
	private $dayOfWeek;

	/**
	 * @ORM\Column(name="start_time", type="string", length=55)
	 */
	private $startTime;

	/**
	 * @ORM\Column(name="end_time", type="string", length=55)
	 */
	private $endTime;

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
	public function getDayOfWeek()
	{
		return $this->dayOfWeek;
	}

	/**
	 * @param mixed $dayOfWeek
	 */
	public function setDayOfWeek($dayOfWeek)
	{
		$this->dayOfWeek = $dayOfWeek;
	}

	/**
	 * @return mixed
	 */
	public function getStartTime()
	{
		return $this->startTime ? "{$this->startTime}:00" : null;
	}

	/**
	 * @param mixed $startTime
	 */
	public function setStartTime($startTime)
	{
		$this->startTime = $startTime;
	}

	/**
	 * @return mixed
	 */
	public function getEndTime()
	{
		return $this->endTime ? "{$this->endTime}:59" : null;
	}

	/**
	 * @param mixed $endTime
	 */
	public function setEndTime($endTime)
	{
		$this->endTime = $endTime;
	}

	/**
	 * Set businessHours
	 *
	 * @param BusinessHours businessHours
	 * @return BusinessHoursItem
	 */
	public function setBusinessHours(BusinessHours $businessHours = null)
	{
		$this->businessHours = $businessHours;

		return $this;
	}

	/**
	 * Get contract
	 *
	 * @return BusinessHours
	 */
	public function getBusinessHours()
	{
		return $this->businessHours;
	}
}
