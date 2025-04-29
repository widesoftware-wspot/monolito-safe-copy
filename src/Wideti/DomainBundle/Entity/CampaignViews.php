<?php


namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="campaign_views")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignViewsRepository")
 */
class CampaignViews
{
    const STEP_PRE = 1;
    const STEP_POS = 2;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="campaignViews")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     */
    private $campaign;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="view_time", type="datetime")
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @ORM\Column(name="type", type="integer")
     */
    protected $type;

	/**
	 * @ORM\Column(name="guest_id", type="string", length=10, nullable=true)
	 */
	private $guestId;

	/**
	 * @ORM\Column(name="guest", type="string", length=50, nullable=true)
	 */
	private $guestMacAddress;

    /**
     * @ORM\Column(name="access_point", type="string", length=50, nullable=true)
     */
    private $accessPoint;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param null $campaign
     * @return $this
     */
    public function setCampaign($campaign = null)
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

    public function addCampaign($campaign)
    {
        $this->setCampaign($campaign);
    }

    /**
     * @return mixed
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param $dateTime
     * @return $this
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

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
     * @param $type
     * @return $this
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
	 */
	public function setGuestId($guestId)
	{
		$this->guestId = $guestId;
	}

	/**
	 * @return mixed
	 */
	public function getGuestMacAddress()
	{
		return $this->guestMacAddress;
	}

	/**
	 * @param mixed $guestMacAddress
	 */
	public function setGuestMacAddress($guestMacAddress)
	{
		$this->guestMacAddress = $guestMacAddress;
	}

    /**
     * @return mixed
     */
    public function getAccessPoint()
    {
        return $this->accessPoint;
    }

    /**
     * @param $accessPoint
     * @return $this
     */
    public function setAccessPoint($accessPoint)
    {
        $this->accessPoint = $accessPoint;

        return $this;
    }
}
