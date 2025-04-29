<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleConfigurationValue
 *
 * @ORM\Table(name="module_configuration_value")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ModuleConfigurationValueRepository")
 */
class ModuleConfigurationValue
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="ModuleConfiguration", inversedBy="value")
     * @ORM\JoinColumn(name="module_configuration_id", referencedColumnName="id")
     */
    private $items;

    /**
     * @var string
     *
     * @ORM\Column(name="`value`", type="text", nullable=true)
     */
    protected $value;

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
     * Set value
     *
     * @param string $value
     * @return ModuleConfigurationValue
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set items
     *
     * @param \Wideti\DomainBundle\Entity\ModuleConfiguration $items
     * @return ModuleConfigurationValue
     */
    public function setItems(\Wideti\DomainBundle\Entity\ModuleConfiguration $items = null)
    {
        $this->items = $items;
    
        return $this;
    }

    /**
     * Get items
     *
     * @return \Wideti\DomainBundle\Entity\ModuleConfiguration 
     */
    public function getItems()
    {
        return $this->items;
    }

    public function setClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }
}