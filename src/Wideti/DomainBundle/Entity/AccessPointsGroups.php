<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;

/**
 * @ORM\Table(name="access_points_groups")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessPointsGroupsRepository")
 */
class AccessPointsGroups
{
    use TimestampableEmbed;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="is_default", type="boolean", options={"default":0})
     */
    protected $isDefault = false;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="group_name", type="string", length=100)
     */
    protected $groupName;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\AccessPoints", mappedBy="group")
     */
    protected $accessPoints;

    /**
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="accessPointsGroups")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     */
    protected $template;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *
     */
    protected $client;

    /**
     * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\Campaign", mappedBy="accessPointsGroups")
     * @Exclude()
     */
    protected $campaigns;

    /**
     * @ORM\Column(name="parent", type="integer", nullable=true)
     */
    protected $parent;

    /**
     * @ORM\Column(name="parent_configurations", type="boolean", options={"default" : false})
     */
    protected $parentConfigurations;

    /**
     * @ORM\Column(name="parent_template", type="boolean", options={"default" : false}, nullable=true)
     */
    protected $parentTemplate;

    /**
     * @ORM\Column(name="is_master", type="boolean", options={"default" : false}, nullable=true)
     */
    private $isMaster;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accessPoints = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->groupName;
    }

    /**
     * @return boolean
     */
    public function getParentConfigurations()
    {
        return $this->parentConfigurations;
    }

    /**
     * @param $configuration
     * @return $this
     */
    public function setParentConfigurations($configuration)
    {
        $this->parentConfigurations = $configuration;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getParentTemplate()
    {
        return $this->parentTemplate;
    }

    /**
     * @param $template
     * @return $this
     */
    public function setParentTemplate($template)
    {
        $this->parentTemplate = $template;
        return $this;
    }

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
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param $isDefault
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    }

    /**
     * @param $groupName
     * @return $this
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

	/**
	 * @param mixed $accessPoints
	 */
	public function setAccessPoints($accessPoints)
	{
		$this->accessPoints = $accessPoints;
	}

    /**
     * @param AccessPoints $accessPoints
     * @return $this
     */
    public function addAccessPoint(AccessPoints $accessPoints)
    {
        $this->accessPoints[] = $accessPoints;

        return $this;
    }

    /**
     * @param AccessPoints $accessPoints
     */
    public function removeAccessPoint(AccessPoints $accessPoints)
    {
        $this->accessPoints->removeElement($accessPoints);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAccessPoints()
    {
        return $this->accessPoints;
    }

    /**
     * @param Template|null $template
     * @return $this
     */
    public function setTemplate(Template $template = null)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client)
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
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @param Campaign $campaigns
     * @return $this
     */
    public function addCampaign(Campaign $campaigns)
    {
        $this->campaigns[] = $campaigns;
        return $this;
    }

    /**
     * @param Campaign $campaign
     */
    public function removeCampaign(Campaign $campaign)
    {
        $this->campaigns->removeElement($campaign);
    }

    /**
     * @return Campaign[]
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * @return mixed
     */
    public function getIsMaster()
    {
        return $this->isMaster;
    }

    /**
     * @param $isMaster
     * @return $this
     */
    public function setIsMaster($isMaster)
    {
        $this->isMaster = $isMaster;
        return $this;
    }
}
