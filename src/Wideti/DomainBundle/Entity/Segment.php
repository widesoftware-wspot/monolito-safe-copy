<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="segments")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SegmentRepository")
 */
class Segment implements \Serializable
{
	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	private $name;

	/**
	 * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\Client", mappedBy="segment")
	 */
	private $clients;

	public function __construct()
	{
		$this->clients = new ArrayCollection();
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
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getClients()
	{
		return $this->clients;
	}

	public function setClient(Client $client){
		$this->clients[] = $client;
		return $this;
	}

	public function __toString()
	{
		return $this->name;
	}

	/**
	 * String representation of object
	 * @link https://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 */
	public function serialize()
	{
		return \serialize(array(
			$this->id,
			$this->name,
		));
	}

	/**
	 * Constructs the object
	 * @link https://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return void
	 * @since 5.1.0
	 */
	public function unserialize($serialized)
	{
		list(
			$this->id,
			$this->name,
			) = \unserialize($serialized);
	}
}
