<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="api_wspot_contracts")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ApiWSpotContractsRepository")
 */
class ApiWSpotContracts
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ApiWSpot", inversedBy="contracts")
     * @ORM\JoinColumn(name="api_wspot_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $api;

    /**
     * @ORM\Column(name="`key`", type="string", length=255)
     */
    private $key;

    /**
     * @ORM\Column(name="`value`", type="string", length=255)
     */
    private $value;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param mixed $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
