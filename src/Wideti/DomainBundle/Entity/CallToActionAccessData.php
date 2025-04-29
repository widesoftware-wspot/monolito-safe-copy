<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="call_to_action_access_data")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignCallToAction\AccessDataRepository")
 */
class CallToActionAccessData
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", targetEntity="campaign")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     */
    private $campaign;

    /**
     * @ORM\Column(name="type", type="string", length=5, nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(name="guest_id", type="integer", nullable=true)
     */
    private $guestId;

    /**
     * @ORM\Column(name="mac_address", type="string", length=50, nullable=false)
     */
    private $macAddress;

    /**
     * @ORM\Column(name="url", type="string", length=150, nullable=true)
     */
    private $url;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="view_date", type="datetime")
     */
    private $viewDate;

    /**
     * @ORM\Column(name="ap_mac_address", type="string", length=50, nullable=false)
     */
    private $apMacAddress;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return CallToActionAccessData
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     * @return CallToActionAccessData
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return CallToActionAccessData
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGuestId()
    {
        return $this->guestId;
    }

    /**
     * @param mixed $guestId
     * @return CallToActionAccessData
     */
    public function setGuestId($guestId)
    {
        $this->guestId = $guestId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * @param mixed $macAddress
     * @return CallToActionAccessData
     */
    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     * @return CallToActionAccessData
     */
    public function setUrl($url)
    {
        if ($url != 'null') {
            $this->url = $url;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getViewDate()
    {
        return $this->viewDate;
    }

    /**
     * @param mixed $viewDate
     * @return CallToActionAccessData
     */
    public function setViewDate($viewDate)
    {
        $this->viewDate = $viewDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApMacAddress()
    {
        return $this->apMacAddress;
    }

    /**
     * @param mixed $apMacAddress
     * @return CallToActionAccessData
     */
    public function setApMacAddress($apMacAddress)
    {
        $this->apMacAddress = $apMacAddress;
        return $this;
    }
}