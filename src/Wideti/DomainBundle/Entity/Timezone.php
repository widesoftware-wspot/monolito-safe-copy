<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="timezone")
 * @ORM\Table(indexes={@ORM\Index(name="idx_zone_id", columns={"zone_id"})})
 * @ORM\Table(indexes={@ORM\Index(name="idx_time_start", columns={"time_start"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\TimezoneRepository")
 */
class Timezone
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="zone_id", type="integer")
     */
    private $zoneId;

    /**
     * @ORM\Column(name="abbreviation", type="string", length=6)
     */
    private $abbreviation;

    /**
     * @ORM\Column(name="time_start", type="decimal", precision=11, scale=0)
     */
    private $timeStart;

    /**
     * @ORM\Column(name="gmt_offset", type="integer")
     */
    private $gmtOffset;

    /**
     * @ORM\Column(name="dst", type="integer", columnDefinition="CHAR(1)")
     */
    private $dst;

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
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * @param mixed $abbreviation
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return mixed
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * @param mixed $timeStart
     */
    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;
    }

    /**
     * @return mixed
     */
    public function getGmtOffset()
    {
        return $this->gmtOffset;
    }

    /**
     * @param mixed $gmtOffset
     */
    public function setGmtOffset($gmtOffset)
    {
        $this->gmtOffset = $gmtOffset;
    }

    /**
     * @return mixed
     */
    public function getDst()
    {
        return $this->dst;
    }

    /**
     * @param mixed $dst
     */
    public function setDst($dst)
    {
        $this->dst = $dst;
    }

}