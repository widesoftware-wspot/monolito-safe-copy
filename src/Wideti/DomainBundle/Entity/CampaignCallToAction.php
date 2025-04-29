<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="campaign_call_to_action")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignCallToAction\PersistCallToActionRepository")
 */
class CampaignCallToAction
{
    const LANDSCAPE = "landscape";
    const PORTRAIT  = "portrait";

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Campaign", mappedBy="campaign")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     */
    private $campaign;

    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(name="label", type="string", length=50, nullable=true)
     */
    private $label;

    /**
     * @ORM\Column(name="redirect_url", type="string", length=100, nullable=true)
     */
    private $redirectUrl;

    /**
     * @ORM\Column(name="portrait_button_width", type="string", length=10, nullable=true)
     */
    private $portraitButtonWidth;

    /**
     * @ORM\Column(name="portrait_button_size", type="string", length=10, nullable=true)
     */
    private $portraitButtonSize;

    /**
     * @ORM\Column(name="portrait_button_color", type="string", length=10, nullable=true)
     */
    private $portraitButtonColor;

    /**
     * @ORM\Column(name="portrait_button_label_size", type="string", length=10, nullable=true)
     */
    private $portraitButtonLabelSize;

    /**
     * @ORM\Column(name="portrait_button_label_color", type="string", length=10, nullable=true)
     */
    private $portraitButtonLabelColor;

    /**
     * @ORM\Column(name="portrait_button_vertical_align", type="string", length=20, nullable=true)
     */
    private $portraitButtonVerticalAlign;

    /**
     * @ORM\Column(name="portrait_button_horizontal_align", type="string", length=20, nullable=true)
     */
    private $portraitButtonHorizontalAlign;

    /**
     * @ORM\Column(name="landscape_button_width", type="string", length=10, nullable=true)
     */
    private $landscapeButtonWidth;

    /**
     * @ORM\Column(name="landscape_button_size", type="string", length=10, nullable=true)
     */
    private $landscapeButtonSize;

    /**
     * @ORM\Column(name="landscape_button_color", type="string", length=10, nullable=true)
     */
    private $landscapeButtonColor;

    /**
     * @ORM\Column(name="landscape_button_label_size", type="string", length=10, nullable=true)
     */
    private $landscapeButtonLabelSize;

    /**
     * @ORM\Column(name="landscape_button_label_color", type="string", length=10, nullable=true)
     */
    private $landscapeButtonLabelColor;

    /**
     * @ORM\Column(name="landscape_button_vertical_align", type="string", length=20, nullable=true)
     */
    private $landscapeButtonVerticalAlign;

    /**
     * @ORM\Column(name="landscape_button_horizontal_align", type="string", length=20, nullable=true)
     */
    private $landscapeButtonHorizontalAlign;

    /**
     * @ORM\Column(name="campaign_type", type="string", length=2, nullable=true)
     */
    private $campaignType;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return CampaignCallToAction
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
     * @return CampaignCallToAction
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return CampaignCallToAction
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     * @return CampaignCallToAction
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param mixed $redirectUrl
     * @return CampaignCallToAction
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonSize()
    {
        return $this->portraitButtonSize;
    }

    /**
     * @param mixed $portraitButtonSize
     * @return CampaignCallToAction
     */
    public function setPortraitButtonSize($portraitButtonSize)
    {
        $this->portraitButtonSize = $portraitButtonSize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonColor()
    {
        return $this->portraitButtonColor;
    }

    /**
     * @param mixed $portraitButtonColor
     * @return CampaignCallToAction
     */
    public function setPortraitButtonColor($portraitButtonColor)
    {
        $this->portraitButtonColor = $portraitButtonColor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonLabelSize()
    {
        return $this->portraitButtonLabelSize;
    }

    /**
     * @param mixed $portraitButtonLabelSize
     * @return CampaignCallToAction
     */
    public function setPortraitButtonLabelSize($portraitButtonLabelSize)
    {
        $this->portraitButtonLabelSize = $portraitButtonLabelSize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonLabelColor()
    {
        return $this->portraitButtonLabelColor;
    }

    /**
     * @param mixed $portraitButtonLabelColor
     * @return CampaignCallToAction
     */
    public function setPortraitButtonLabelColor($portraitButtonLabelColor)
    {
        $this->portraitButtonLabelColor = $portraitButtonLabelColor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonVerticalAlign()
    {
        return $this->portraitButtonVerticalAlign;
    }

    /**
     * @param mixed $portraitButtonVerticalAlign
     * @return CampaignCallToAction
     */
    public function setPortraitButtonVerticalAlign($portraitButtonVerticalAlign)
    {
        $this->portraitButtonVerticalAlign = $portraitButtonVerticalAlign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonHorizontalAlign()
    {
        return $this->portraitButtonHorizontalAlign;
    }

    /**
     * @param mixed $portraitButtonHorizontalAlign
     * @return CampaignCallToAction
     */
    public function setPortraitButtonHorizontalAlign($portraitButtonHorizontalAlign)
    {
        $this->portraitButtonHorizontalAlign = $portraitButtonHorizontalAlign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonSize()
    {
        return $this->landscapeButtonSize;
    }

    /**
     * @param mixed $landscapeButtonSize
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonSize($landscapeButtonSize)
    {
        $this->landscapeButtonSize = $landscapeButtonSize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonColor()
    {
        return $this->landscapeButtonColor;
    }

    /**
     * @param mixed $landscapeButtonColor
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonColor($landscapeButtonColor)
    {
        $this->landscapeButtonColor = $landscapeButtonColor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonLabelSize()
    {
        return $this->landscapeButtonLabelSize;
    }

    /**
     * @param mixed $landscapeButtonLabelSize
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonLabelSize($landscapeButtonLabelSize)
    {
        $this->landscapeButtonLabelSize = $landscapeButtonLabelSize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonLabelColor()
    {
        return $this->landscapeButtonLabelColor;
    }

    /**
     * @param mixed $landscapeButtonLabelColor
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonLabelColor($landscapeButtonLabelColor)
    {
        $this->landscapeButtonLabelColor = $landscapeButtonLabelColor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonVerticalAlign()
    {
        return $this->landscapeButtonVerticalAlign;
    }

    /**
     * @param mixed $landscapeButtonVerticalAlign
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonVerticalAlign($landscapeButtonVerticalAlign)
    {
        $this->landscapeButtonVerticalAlign = $landscapeButtonVerticalAlign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonHorizontalAlign()
    {
        return $this->landscapeButtonHorizontalAlign;
    }

    /**
     * @param mixed $landscapeButtonHorizontalAlign
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonHorizontalAlign($landscapeButtonHorizontalAlign)
    {
        $this->landscapeButtonHorizontalAlign = $landscapeButtonHorizontalAlign;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortraitButtonWidth()
    {
        return $this->portraitButtonWidth;
    }

    /**
     * @param mixed $portraitButtonWidth
     * @return CampaignCallToAction
     */
    public function setPortraitButtonWidth($portraitButtonWidth)
    {
        $this->portraitButtonWidth = $portraitButtonWidth;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLandscapeButtonWidth()
    {
        return $this->landscapeButtonWidth;
    }

    /**
     * @param $landscapeButtonWidth
     * @return CampaignCallToAction
     */
    public function setLandscapeButtonWidth($landscapeButtonWidth)
    {
        $this->landscapeButtonWidth = $landscapeButtonWidth;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaignType()
    {
        return $this->campaignType;
    }

    /**
     * @param mixed $campaignType
     * @return CampaignCallToAction
     */
    public function setCampaignType($campaignType)
    {
        $this->campaignType = $campaignType;
        return $this;
    }
}