<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Table(name="campaign_media_video")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignMediaVideoRepository")
 */
class CampaignMediaVideo
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
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $client;

    /**
     * @ORM\Column(name="step", type="string", length=20)
     */
    private $step;

	/**
	 * @ORM\Column(name="url_mp4", type="string", length=255, nullable=true)
	 */
    private $urlMp4;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(name="orientation", type="string", length=20)
     */
    private $orientation;

    /**
     * @ORM\Column(name="bucket_id", type="string", length=255, nullable=true)
     */
    private $bucketId;

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
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * @param mixed $orientation
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
    }

    /**
     * @return mixed
     */
    public function getBucketId()
    {
        return $this->bucketId;
    }

    /**
     * @param mixed $bucketId
     */
    public function setBucketId($bucketId)
    {
        $this->bucketId = $bucketId;
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

    public function setCampaign(\Wideti\DomainBundle\Entity\Campaign $campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    public function getCampaign()
    {
        return $this->campaign;
    }

	/**
	 * @return mixed
	 */
	public function getUrlMp4()
	{
		return $this->urlMp4;
	}

	/**
	 * @param mixed $urlMp4
	 */
	public function setUrlMp4($urlMp4)
	{
		$this->urlMp4 = $urlMp4;
	}
}
