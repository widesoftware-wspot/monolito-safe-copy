<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="access_points_extra_config")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessPointsExtraConfigRepository")
 */
class AccessPointExtraConfig
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\ExtraConfigType")
     * @ORM\JoinColumn(name="config_name", referencedColumnName="config_name", nullable=false)
     */
    private $configType;

    /**
     * @ORM\Column(name="value", type="string", length=50, nullable=false)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\AccessPoints", cascade={"persist"})
     * @ORM\JoinColumn(name="ap_id", referencedColumnName="id")
     *
     */
    protected $ap;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getConfigType()
    {
        return $this->configType;
    }

    /**
     * @param string $configType
     */
    public function setConfigType($configType)
    {
        $this->configType = $configType;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return AccessPoints
     */
    public function getAp()
    {
        return $this->ap;
    }

    /**
     * @param AccessPoints $ap
     */
    public function setAp($ap)
    {
        $this->ap = $ap;
    }


}