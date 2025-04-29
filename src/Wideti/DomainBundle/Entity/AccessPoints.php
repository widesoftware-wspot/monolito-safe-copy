<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;

/**
 * @ORM\Table(
 *      name="access_points",
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="unique_identifier",
 *              columns={
 *                  "identifier",
 *                  "client_id"
 *              }
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessPointsRepository")
 */
class AccessPoints
{
    use TimestampableEmbed;

    const INACTIVE = 0;
    const ACTIVE   = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="status", type="boolean", options={"default":1} )
     */
    protected $status = self::ACTIVE;

    /**
     * Type option
     * @ORM\Column(name="vendor", type="string", length=50, nullable=true)
     */
    private $vendor;

    /**
     * Mac Address Friendly name
     * @Assert\Regex(
     *      pattern="/[=+|{}['_!@#$%*]/",
     *      match=false,
     *      message="Este campo não aceita caracteres especiais",
     *      groups={"AccessPoints"}
     * )
     * @ORM\Column(name="friendly_name", type="string", length=100)
     */
    private $friendlyName;

    /**
     * Access Point Identifier
     * @Assert\NotBlank(
     *      message = "Este campo deve ser preenchido"
     * )
     * @ORM\Column(name="identifier", type="string", length=100, nullable=true)
     */
    private $identifier;

    /**
     * Access Point Local
     * @ORM\Column(name="local", type="string", length=100, nullable=true)
     */
    private $local;

    /**
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity="AccessPointsGroups", inversedBy="accessPoints")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * @Assert\NotNull(message="Ponto de acesso deve possuir um grupo")
     */
    protected $group;

    /**
	 * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\BusinessHours", mappedBy="accessPoints")
	 * @Exclude()
	 */
    private $businessHours;

    /**
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="accessPoints")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $template;

	/**
	 * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\Campaign", mappedBy="accessPoints")
	 * @Exclude()
	 */
	protected $campaigns;

	/**
	 * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\ApiEgoi", mappedBy="accessPoints")
	 * @Exclude()
	 */
	protected $apiEgoi;

    /**
     * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\ApiRDStation", mappedBy="accessPoints")
     * @Exclude()
     */
    protected $apiRDStation;

    /**
     * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\AccessCode", mappedBy="accessPoints")
     * @Exclude()
     */
    protected $accessCode;

    /**
     * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $client;

    /**
     * @var boolean
     * @ORM\Column(name="request_verified", type="boolean", nullable=false, options={"default":false})
     */
    protected $requestVerified = false;

    /**
     * @var boolean
     * @ORM\Column(name="radius_verified", type="boolean", nullable=false, options={"default":false})
     */
    protected $radiusVerified = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="verified_date", type="datetime", length=100, nullable=true)
     */
    protected $verifiedDate;

    /**
     * @ORM\Column(name="timezone", type="string", length=100, nullable=true)
     */
    public $timezone;

    /**
     * @ORM\Column(name="public_ip", type="string", length=18, nullable=true)
     */
    private $publicIp;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Vendor", fetch="LAZY")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="id")
     */
    protected $vendorId;

    /**
     * @ORM\OneToMany(targetEntity="AccessPointMonitoring", mappedBy="accessPoint", fetch="EXTRA_LAZY")
     * @Exclude()
     */
    private $monitoring;

    /**
     * @ORM\OneToOne(targetEntity="Wideti\DomainBundle\Entity\DeskbeeDevice", mappedBy="accessPoint", cascade={"persist"})
     */
    private $deskbeeDevice;

    /**
     * AccessPoints constructor.
     */
    public function __construct()
    {
        $this->radiusVerified  = 0;
        $this->requestVerified = 0;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->friendlyName;
    }

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
     * @return mixed
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    /**
     * @param $friendlyName
     * @return $this
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set group
     *
     * @param \Wideti\DomainBundle\Entity\AccessPointsGroups $group
     * @return AccessPoints
     */
    public function setGroup(\Wideti\DomainBundle\Entity\AccessPointsGroups $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Wideti\DomainBundle\Entity\AccessPointsGroups
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return mixed
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @param $local
     * @return $this
     */
    public function setLocal($local)
    {
        $this->local = $local;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param $location
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int|string
     */
    public function getStatusAsString()
    {
        switch ($this->status) {
            case self::INACTIVE:
                return "Inativo";
                break;
            case self::ACTIVE:
                return "Ativo";
                break;
            default:
                return self::ACTIVE;
        }
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param $vendor
     * @return $this
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * Add campaigns
     *
     * @param \Wideti\DomainBundle\Entity\Campaign $campaigns
     * @return AccessPoints
     */
    public function addCampaign(\Wideti\DomainBundle\Entity\Campaign $campaigns)
    {
        $this->campaigns[] = $campaigns;

        return $this;
    }

    /**
     * Remove campaigns
     *
     * @param \Wideti\DomainBundle\Entity\Campaign $campaigns
     */
    public function removeCampaign(\Wideti\DomainBundle\Entity\Campaign $campaigns)
    {
        $this->campaigns->removeElement($campaigns);
    }

    /**
     * @return mixed
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    public function getBusinessHours()
    {
        return $this->businessHours;
    }

    /**
     * Set template
     *
     * @param \Wideti\DomainBundle\Entity\Template $template
     * @return AccessPoints
     */
    public function setTemplate(\Wideti\DomainBundle\Entity\Template $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return bool
     */
    public function isRequestVerified()
    {
        return $this->requestVerified;
    }

    /**
     * @param $requestVerified
     * @return $this
     */
    public function setRequestVerified($requestVerified)
    {
        $this->requestVerified = $requestVerified;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRadiusVerified()
    {
        return $this->radiusVerified;
    }

    /**
     * @param $radiusVerified
     * @return $this
     */
    public function setRadiusVerified($radiusVerified)
    {
        $this->radiusVerified = $radiusVerified;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getVerifiedDate()
    {
        return $this->verifiedDate;
    }

    /**
     * @param $verifiedDate
     * @return $this
     */
    public function setVerifiedDate($verifiedDate)
    {
        $this->verifiedDate = $verifiedDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        $timezone = $this->timezone;
        $timezone = is_null($timezone) ? TimezoneService::DEFAULT_TIMEZONE : $this->timezone;
        return $timezone;
    }

    /**
     * @param $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        is_null($timezone) ? $this->timezone = TimezoneService::DEFAULT_TIMEZONE : $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublicIp() {
        return $this->publicIp;
    }

    /**
     * @param $publicIp
     * @return $this
     */
    public function setPublicIp($publicIp) {
        $this->publicIp = $publicIp;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isFullVerified()
    {
        return $this->requestVerified && $this->radiusVerified;
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param Vendor $vendorId
     */
    public function setVendorId($vendorId)
    {
        $this->vendorId = $vendorId;
    }

    /**
     * @return mixed
     */
    public function getMonitoring()
    {
        return $this->monitoring;
    }

    /**
     * @return mixed
     */
    public function getDeskbeeDevice()
    {
        return $this->deskbeeDevice;
    }

    /**
     * SetDeskbeeDevice
     *
     * @param \Wideti\DomainBundle\Entity\DeskbeeDevice $deskbeeDevice
     * @return AccessPoints
     */
    public function setDeskbeeDevice(\Wideti\DomainBundle\Entity\DeskbeeDevice $deskbeeDevice = null)
    {
        $this->deskbeeDevice = $deskbeeDevice;

        return $this;
    }
}
