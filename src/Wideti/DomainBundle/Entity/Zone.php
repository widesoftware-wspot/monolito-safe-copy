<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="zone")
 * @ORM\Table(indexes={@ORM\Index(name="idx_country_code", columns={"country_code"})})
 * @ORM\Table(indexes={@ORM\Index(name="idx_zone_name", columns={"zone_name"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ZoneRepository")
 */
class Zone
{
    /**
     * @ORM\Column(name="zone_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $zoneId;

    /**
     * @ORM\Column(name="country_code", type="string", columnDefinition="CHAR(2)")
     */
    private $countryCode;

    /**
     * @ORM\Column(name="zone_name", type="string", length=35)
     */
    private $zoneName;


    /**
     * @return mixed
     */
    public function getZoneId()
    {
        return $this->zoneId;
    }

    /**
     * @param mixed $zoneId
     */
    public function setZoneId($zoneId)
    {
        $this->zoneId = $zoneId;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param mixed $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @return mixed
     */
    public function getZoneName()
    {
        return $this->zoneName;
    }

    /**
     * @param mixed $zoneName
     */
    public function setZoneName($zoneName)
    {
        $this->zoneName = $zoneName;
    }

}