<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Table(name="video_skip")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\VideoSkipRepository")
 */
class VideoSkip
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Campaign")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     * @Exclude()
     */
    private $campaignId;

	/**
	 * @ORM\Column(name="skip", type="integer", options={"default":0} )
	 */
    private $skip;

    /**
     * @ORM\Column(name="step", type="string")
     */
    private $step;



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
    public function getSkip()
    {
        return $this->skip;
    }

    /**
     * @param mixed $skip
     */
    public function setSkip($skip)
    {
        $this->skip = $skip;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param mixed $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * Set campaign
     * @param \Wideti\DomainBundle\Entity\Campaign $campaignId
     * @return VideoSkip
     */
    public function setCampaignId($id)
    {
        $this->campaignId = $id;
        return $this->campaignId;
    }

    /**
     * Get campaign
     *
     * @return \Wideti\DomainBundle\Entity\Campaign $campaignId
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }
}
