<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\PlanRepository")
 */
class Plan implements \Serializable
{
    const BASIC = 'basic';
    const PRO   = 'pro';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="short_code", type="string", length=255)
	 */
	private $shortCode;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="plan", type="string", length=255)
	 */
	private $plan;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\Client", mappedBy="plan")
     */
    private $clients;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

	/**
	 * @return string
	 */
	public function getShortCode()
	{
		return $this->shortCode;
	}

	/**
	 * @param string $shortCode
	 */
	public function setShortCode($shortCode)
	{
		$this->shortCode = $shortCode;
	}

    /**
     * Set plan
     *
     * @param string $plan
     *
     * @return Plan
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * Get plan
     *
     * @return string
     */
    public function getPlan()
    {
        return $this->plan;
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
        return $this->plan;
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
            $this->plan,
            $this->shortCode,
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
            $this->plan,
            $this->shortCode
            ) = \unserialize($serialized);
    }
}

