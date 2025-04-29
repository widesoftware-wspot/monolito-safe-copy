<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleConfiguration
 *
 * @ORM\Table(
 *      name="module_configuration",
 *      indexes={@ORM\Index(name="key_idx", columns={"`key`"})},
 *      )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ModuleConfigurationRepository")
 */
class ModuleConfiguration
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Module", inversedBy="configurations")
     * @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     */
    private $module;

    /**
     * @var string
     *
     * @ORM\Column(name="`key`", type="string", length=255)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="text")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="text")
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="json_array", nullable=true)
     */
    protected $params;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\ModuleConfigurationValue",
     *      mappedBy="items",
     *      cascade={"persist", "remove"}
     * )
     */
    protected $value;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->value = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set key
     *
     * @param string $key
     * @return ModuleConfiguration
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return ModuleConfiguration
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return ModuleConfiguration
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set params
     *
     * @param array $params
     * @return ModuleConfiguration
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set module
     *
     * @param \Wideti\DomainBundle\Entity\Module $module
     * @return ModuleConfiguration
     */
    public function setModule(\Wideti\DomainBundle\Entity\Module $module = null)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return \Wideti\DomainBundle\Entity\Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Add value
     *
     * @param \Wideti\DomainBundle\Entity\ModuleConfigurationValue $value
     * @return ModuleConfiguration
     */
    public function addValue(\Wideti\DomainBundle\Entity\ModuleConfigurationValue $value)
    {
        $this->value[] = $value;

        return $this;
    }

    /**
     * Remove value
     *
     * @param \Wideti\DomainBundle\Entity\ModuleConfigurationValue $value
     */
    public function removeValue(\Wideti\DomainBundle\Entity\ModuleConfigurationValue $value)
    {
        $this->value->removeElement($value);
    }

    /**
     * Get value
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getValue()
    {
        return $this->value;
    }
}