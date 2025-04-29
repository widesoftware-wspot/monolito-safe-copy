<?php

namespace Wideti\DomainBundle\Document\Group;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ODM\Document(
 *      collection="groups",
 *      repositoryClass="Wideti\DomainBundle\Document\Repository\GroupRepository"
 * )
 * @Constraints\Unique(fields={"name"}, message="Grupo já cadastrado")
 */
class Group
{
    const GROUP_DEFAULT     = "guest";
    const GROUP_EMPLOYEE    = "employee";

    /**
     * @ODM\Id()
     */
    private $id;
    /**
     * @ODM\String()
     */
    private $name;
    /**
     * @ODM\String()
     */
    private $shortcode;

    /**
     * @ODM\EmbedMany(targetDocument="Configuration")
     */
    private $configurations = [];

    /**
     * @ODM\Field(type="boolean")
     * @var boolean
     */
    private $default;

    /**
     * @ODM\EmbedMany(targetDocument="AccessPoint")
     */
    private $accessPoint = [];

    /**
     * @ODM\EmbedMany(targetDocument="AccessPointGroup")
     */
    private $accessPointGroup = [];

    /**
     * @ODM\Field(type="boolean")
     */
    private $inAccessPoints = false;

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
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
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getShortcode()
    {
        return $this->shortcode;
    }

    /**
     * @param mixed $shortcode
     */
    public function setShortcode($shortcode)
    {
        $this->shortcode = $shortcode;
    }

    /**
     * @return Configuration[]
     */
    public function getConfigurations()
    {
        return $this->configurations;
    }

    /**
     * @param mixed $configurations
     */
    public function setConfigurations($configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * Set inAccessPoints
     *
     * @param boolean $inAccessPoints
     * @return self
     */
    public function setInAccessPoints($inAccessPoints)
    {
        $this->inAccessPoints = $inAccessPoints;
        return $this;
    }

    /**
     * Get inAccessPoints
     *
     * @return boolean $inAccessPoints
     */
    public function getInAccessPoints()
    {
        return $this->inAccessPoints;
    }

    /**
     * @return mixed
     */
    public function getAccessPoint()
    {
        return $this->accessPoint;
    }

    /**
     * @param mixed $accessPoint
     */
    public function setAccessPoint($accessPoint)
    {
        $this->accessPoint = $accessPoint;
    }

    /**
     * @return mixed
     */
    public function getAccessPointGroup()
    {
        return $this->accessPointGroup;
    }

    /**
     * @param mixed $accessPointGroup
     */
    public function setAccessPointGroup($accessPointGroup)
    {
        $this->accessPointGroup = $accessPointGroup;
    }

    public function addAccessPoint(AccessPoint $accessPoint)
    {
        $this->accessPoint[] = $accessPoint;
    }

    public function addAccessPointGroup(AccessPointGroup $accessPointGroup)
    {
        $this->accessPointGroup[] = $accessPointGroup;
    }

    public function addConfiguration(Configuration $configuration)
    {
        $this->configurations[] = $configuration;
    }

    public function isConfigActive($shortcode = "")
    {
        if (empty($shortcode)) {
            return false;
        }
        $configurations = $this->getConfigurations();

        /**
         * @var Configuration $config
         */
        foreach ($configurations as $config) {
            if ($config->getShortcode() == $shortcode) {
                $configValue = $config->getConfigurationValueByKey("enable_" . $shortcode);
                $value = $configValue->getValue();
                return (!is_null($value) || empty($value)) ?  $value :  false;
            }
        }

        return false;
    }

}
