<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="segmentation")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SegmentationRepository")
 */
class Segmentation
{
	use TimestampableEmbed;

	const ACTIVE    = "active";
	const INACTIVE  = "inactive";
	const DELETED   = "deleted";

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\Column(name="client_id", type="integer", nullable=true)
	 */
	private $client;

	/**
	 * @ORM\Column(name="status", type="string", length=50, nullable=false)
	 */
	private $status;

	/**
	 * @ORM\Column(name="title", type="string", length=255, nullable=true)
	 */
	private $title;

	/**
	 * @ORM\Column(name="filter", type="json_array", nullable=true)
	 */
	private $filter;

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->title;
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
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
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
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * @param mixed $filter
	 */
	public function setFilter($filter)
	{
		$this->filter = $filter;
	}

	public function __set($key, $value)
	{
		$this->$key = $value;
	}

	public function __get($key)
	{
		return $this->$key;
	}
}
