<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="campaign_hours")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignHoursRepository")
 */
class CampaignHours
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="campaignHours")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     */
    private $campaign;

    /**
     * Start Time
     * @Assert\NotBlank(groups={"default"})
     * @ORM\Column(name="start_time", type="string", length=5, nullable=true)
     */
    private $startTime;

    /**
     * End Time
     * @Assert\NotBlank(groups={"default"})
     * @ORM\Column(name="end_time", type="string", length=5, nullable=true)
     */
    private $endTime;

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
     * Set campaign
     *
     * @param \Wideti\DomainBundle\Entity\Campaign $campaign
     * @return CampaignHours
     */
    public function setCampaign(\Wideti\DomainBundle\Entity\Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Wideti\DomainBundle\Entity\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    public function addCampaign(\Wideti\DomainBundle\Entity\Campaign $campaign)
    {
        $this->setCampaign($campaign);
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime ? "{$this->startTime}:00" : null;
    }

    /**
     * @param mixed $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime ? "{$this->endTime}:59" : null;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }
}
