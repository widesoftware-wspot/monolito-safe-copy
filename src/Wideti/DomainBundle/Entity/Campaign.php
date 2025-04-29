<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="campaign")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignRepository")
 */
class Campaign
{
    use TimestampableEmbed;

    const STATUS_DRAFT      = 0;
    const STATUS_ACTIVE     = 1;
    const STATUS_INACTIVE   = 2;
    const STATUS_EXPIRED    = 3;

    const STEP_PRE_LOGIN = "pre";
    const STEP_POS_LOGIN = "pos";

    const ALL_ACCESS_POINTS       = 0;
    const IN_ACCESS_POINTS        = 1;
    const IN_ACCESS_POINTS_GROUPS = 2;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank(groups={"default"})
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @Assert\NotBlank(groups={"default"})
     * @ORM\Column(name="start_date", type="date")
     */
    private $startDate;

    /**
     * @Assert\NotBlank(groups={"default"})
     * @ORM\Column(name="end_date", type="date")
     */
    private $endDate;

    /**
     * Campaign SSID
     * @ORM\Column(name="ssid", type="string", length=100, nullable=true)
     */
    private $ssid;

    /**
     * @ORM\Column(name="status", type="integer", options={"default":0} )
     */
    private $status = self::STATUS_DRAFT;

    /**
     * @ORM\Column(name="bg_color", type="string", options={"default":"#000000"})
     */
    private $bgColor;

    /**
     * @Assert\Url(
     *      groups  = {"default"},
     *      message = "Insira uma URL válida"
     * )
     * @ORM\Column(name="redirect_url", type="string", length=100)
     */
    private $redirectUrl;

    /**
     * @ORM\Column(name="in_access_points", type="integer")
     * @Exclude()
     */
    protected $inAccessPoints = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $client;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\CampaignHours",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $campaignHours;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\CampaignMediaImage",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $campaignMediaImage;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\CampaignMediaVideo",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $campaignMediaVideo;

    /**
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="campaign")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $template;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AccessPoints", inversedBy="campaigns")
     * @ORM\JoinTable(name="campaign_access_points",
     *   joinColumns={
     *     @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="access_point_id", referencedColumnName="id")
     *   }
     * )
     * @Exclude()
     */
    protected $accessPoints;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AccessPointsGroups", inversedBy="campaigns")
     * @ORM\JoinTable(name="campaign_access_points_groups",
     *   joinColumns={
     *     @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="access_point_group_id", referencedColumnName="id")
     *   }
     * )
     * @Exclude()
     */
    protected $accessPointsGroups;

    /**
     * @ORM\OneToOne(
     *      targetEntity="Wideti\DomainBundle\Entity\CampaignCallToAction",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $callToAction;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\CallToActionAccessData",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $callToActionAccessData;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Wideti\DomainBundle\Entity\CampaignViews",
     *      mappedBy="campaign",
     *      cascade={"persist", "remove"}
     * )
     * @Exclude()
     */
    protected $campaignViews;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->campaignHours        = new ArrayCollection();
        $this->campaignMediaImage   = new ArrayCollection();
        $this->campaignMediaVideo   = new ArrayCollection();
        $this->accessPoints         = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

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
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param mixed $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * Add campaignHours
     *
     * @param \Wideti\DomainBundle\Entity\CampaignHours $campaignHours
     * @return Campaign
     */
    public function addCampaignHours(\Wideti\DomainBundle\Entity\CampaignHours $campaignHours)
    {
        $campaignHours->addCampaign($this);

        $this->campaignHours->add($campaignHours);
    }

    /**
     * Remove campaignHours
     *
     * @param \Wideti\DomainBundle\Entity\CampaignHours $campaignHours
     */
    public function removeCampaignHours(\Wideti\DomainBundle\Entity\CampaignHours $campaignHours)
    {
        $this->campaignHours->removeElement($campaignHours);
    }

    /**
     * Get campaignHours
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignHours()
    {
        return $this->campaignHours;
    }

    /**
     * Get campaignMediaImage
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignMediaImage()
    {
        return $this->campaignMediaImage;
    }

    /**
     * @return ArrayCollection
     */
    public function getCampaignMediaVideo()
    {
        return $this->campaignMediaVideo;
    }

    /**
     * @param mixed $campaignHours
     */
    public function setCampaignHours($campaignHours)
    {
        $this->campaignHours = $campaignHours;
    }

    /**
     * @return mixed
     */
    public function getSsid()
    {
        return $this->ssid;
    }

    /**
     * @param mixed $ssid
     */
    public function setSsid($ssid)
    {
        $this->ssid = $ssid;
    }

    /**
     * Set template
     *
     * @param \Wideti\DomainBundle\Entity\Template $template
     * @return Campaign
     */
    public function setTemplate(\Wideti\DomainBundle\Entity\Template $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return \Wideti\DomainBundle\Entity\Template
     */
    public function getTemplate()
    {
        return $this->template;
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
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatusAsString($status)
    {
        switch ($status) {
            case self::STATUS_INACTIVE:
                return 'Inativo';
                break;
            case self::STATUS_ACTIVE:
                return 'Ativo';
                break;
            case self::STATUS_DRAFT:
                return 'Rascunho';
                break;
            case self::STATUS_EXPIRED:
                return 'Expirada';
                break;
            default:
                return 'Deconhecido';
                break;
        }
    }

    /**
     * Get campaignViews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignViews()
    {
        return $this->campaignViews;
    }

    /**
     * @param mixed $campaignViews
     */
    public function setCampaignViews($campaignViews)
    {
        $this->campaignViews = $campaignViews;
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
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Add campaignHours
     *
     * @param \Wideti\DomainBundle\Entity\CampaignHours $campaignHours
     * @return Campaign
     */
    public function addCampaignHour(\Wideti\DomainBundle\Entity\CampaignHours $campaignHours)
    {
        $this->campaignHours[] = $campaignHours;
    
        return $this;
    }

    /**
     * Remove campaignHours
     *
     * @param \Wideti\DomainBundle\Entity\CampaignHours $campaignHours
     */
    public function removeCampaignHour(\Wideti\DomainBundle\Entity\CampaignHours $campaignHours)
    {
        $this->campaignHours->removeElement($campaignHours);
    }

    /**
     * Add accessPoints
     *
     * @param \Wideti\DomainBundle\Entity\AccessPoints $accessPoints
     * @return Campaign
     */
    public function addAccessPoint(\Wideti\DomainBundle\Entity\AccessPoints $accessPoints)
    {
        $this->accessPoints[] = $accessPoints;
    
        return $this;
    }

    /**
     * Remove accessPoints
     *
     * @param \Wideti\DomainBundle\Entity\AccessPoints $accessPoints
     */
    public function removeAccessPoint(\Wideti\DomainBundle\Entity\AccessPoints $accessPoints)
    {
        $this->accessPoints->removeElement($accessPoints);
    }

    /**
     * Get accessPoints
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccessPoints()
    {
        return $this->accessPoints;
    }

    /**
     * @param AccessPoints[] $accessPoints
     */
    public function setAccessPoints(array $accessPoints)
    {
        $this->accessPoints = $accessPoints;
    }

    public function setClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getInAccessPoints()
    {
        return $this->inAccessPoints;
    }

    public function setInAccessPoints($inAccessPoints)
    {
        $this->inAccessPoints = ($inAccessPoints) ?: 0;
    }

    /**
     * @return mixed
     */
    public function getBgColor()
    {
        return $this->bgColor;
    }

    /**
     * @param mixed $bgColor
     */
    public function setBgColor($bgColor)
    {
        $this->bgColor = $bgColor;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccessPointsGroups()
    {
        return $this->accessPointsGroups;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $accessPointsGroups | AccessPointsGroups[]
     */
    public function setAccessPointsGroups($accessPointsGroups)
    {
        $this->accessPointsGroups = $accessPointsGroups;
    }

    /**
     * @param \Wideti\DomainBundle\Entity\AccessPointsGroups $accessPointsGroups
     */
    public function addAcessPointsGroups(AccessPointsGroups $accessPointsGroups)
    {
        $this->accessPointsGroups[] = $accessPointsGroups;
    }

    /**
     * @return CampaignCallToAction
     */
    public function getCallToAction()
    {
        return $this->callToAction;
    }

    /**
     * @param mixed $callToAction
     * @return $this
     */
    public function setCallToAction($callToAction)
    {
        $this->callToAction = $callToAction;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallToActionAccessData()
    {
        return $this->callToActionAccessData;
    }

    /**
     * @param mixed $callToActionAccessData
     * @return Campaign
     */
    public function setCallToActionAccessData($callToActionAccessData)
    {
        $this->callToActionAccessData = $callToActionAccessData;
        return $this;
    }
}
