<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Module
 *
 * @ORM\Table(name="modules")
 * @UniqueEntity(fields={"shortcode"}, message="ShortCode já cadastrado na base de dados.")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ModuleRepository")
 */
class Module
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="shortcode", type="string", length=100, unique=true)
     */
    private $shortCode;

    /**
     * Name
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\ModuleConfiguration",
     *      mappedBy="module",
     *      cascade={"persist", "remove"}
     * )
     */
    protected $configurations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\Client", mappedBy="module")
     */
    protected $client;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->configurations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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
     * @param string $shortCode
     * @return Configuration
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Configuration
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add configurations
     *
     * @param \Wideti\DomainBundle\Entity\ModuleConfiguration $configurations
     * @return Module
     */
    public function addConfiguration(\Wideti\DomainBundle\Entity\ModuleConfiguration $configurations)
    {
        $this->configurations[] = $configurations;

        return $this;
    }

    /**
     * Remove configurations
     *
     * @param \Wideti\DomainBundle\Entity\ModuleConfiguration $configurations
     */
    public function removeConfiguration(\Wideti\DomainBundle\Entity\ModuleConfiguration $configurations)
    {
        $this->configurations->removeElement($configurations);
    }

    /**
     * Get configurations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConfigurations()
    {
        return $this->configurations;
    }

    /**
     * Add client
     *
     * @param \Wideti\DomainBundle\Entity\Client $client
     * @return Configuration
     */
    public function addClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client[] = $client;

        return $this;
    }

    /**
     * Remove client
     *
     * @param \Wideti\DomainBundle\Entity\Client $client
     */
    public function removeClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client->removeElement($client);
    }

    /**
     * Get client
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClient()
    {
        return $this->client;
    }
}