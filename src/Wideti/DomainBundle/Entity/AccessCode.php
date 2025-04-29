<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="access_code")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessCodeRepository")
 */
class AccessCode
{
	use TimestampableEmbed;

    const ACTIVE            = true;
    const INACTIVE          = false;

    const TYPE_RANDOM              = 'random';
    const TYPE_PREDEFINED          = 'predefined';
    const STEP_LOGIN               = 'login';
    const STEP_SIGNUP              = 'signup';
    const STEP_SIGNUP_CONFIRMATION = 'signup_confirmation';
    const STEP_SOCIAL              = 'social';
    const STEP_SIGNIN              = 'signin';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 *
	 */
	protected $client;

    /**
     * @ORM\Column(name="enable", type="boolean", options={"default":1} )
     */
    private $enable = 1;

    /**
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(name="step", type="string", length=50, nullable=false)
     */
    private $step;

    /**
     * @ORM\Column(name="lot_number", type="string", length=50, nullable=true)
     */
    private $lotNumber;

    /**
     * @ORM\Column(name="quantity", type="integer", nullable=true)
     */
    private $quantity;

    /**
     * @ORM\Column(name="connection_time", type="string", length=50, nullable=true)
     */
    private $connectionTime;

    /**
     * @ORM\Column(name="period_from", type="datetime", nullable=true)
     */
    private $periodFrom;

    /**
     * @ORM\Column(name="period_to", type="datetime", nullable=true)
     */
    private $periodTo;

	/**
	 * @ORM\Column(name="in_access_points", type="integer")
	 */
	private $inAccessPoints = 0;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="AccessPoints", inversedBy="accessCode")
	 * @ORM\JoinTable(name="access_code_access_points",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="access_code_id", referencedColumnName="id")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="access_point_id", referencedColumnName="id")
	 *   }
	 * )
	 * @Exclude()
	 */
	protected $accessPoints;

    /**
     * @ORM\Column(name="logotipo", type="text", nullable=true)
     */
    private $logotipo;

    /**
     * @Assert\Image(maxWidth = 250,maxHeight = 250)
     */
    private $fileLogotipo;

    /**
     * @ORM\Column(name="background_color", type="string", length=50, nullable=true)
     */
    private $backgroundColor;

    /**
     * @ORM\Column(name="font_color", type="string", length=50, nullable=true)
     */
    private $fontColor;

    /**
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\OneToMany(targetEntity="AccessCodeCodes", mappedBy="accessCode", fetch="EXTRA_LAZY")
     * @Exclude()
     */
    private $codes;

    private $code;

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->accessPoints = new ArrayCollection();
		$this->codes        = new ArrayCollection();
	}

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
	public function getClient()
	{
		return $this->client;
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
     * @return mixed
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * @param mixed $enable
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;
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
     */
    public function setType($type)
    {
        $this->type = $type;
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
    public function getLotNumber()
    {
        return $this->lotNumber;
    }

    /**
     * @param mixed $lotNumber
     */
    public function setLotNumber($lotNumber)
    {
        $this->lotNumber = $lotNumber;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getConnectionTime()
    {
        return $this->connectionTime;
    }

    /**
     * @param mixed $connectionTime
     */
    public function setConnectionTime($connectionTime)
    {
        $this->connectionTime = $connectionTime;
    }

    /**
     * @return mixed
     */
    public function getPeriodFrom()
    {
        return $this->periodFrom;
    }

    /**
     * @param mixed $periodFrom
     */
    public function setPeriodFrom($periodFrom)
    {
        $this->periodFrom = $periodFrom;
    }

    /**
     * @return mixed
     */
    public function getPeriodTo()
    {
        return $this->periodTo;
    }

    /**
     * @param mixed $periodTo
     */
    public function setPeriodTo($periodTo)
    {
        $this->periodTo = $periodTo;
    }

	/**
	 * @return mixed
	 */
	public function getInAccessPoints()
	{
		return $this->inAccessPoints;
	}

	/**
	 * @param mixed $inAccessPoints
	 */
	public function setInAccessPoints($inAccessPoints)
	{
		$this->inAccessPoints = $inAccessPoints;
	}

	/**
	 * Add accessPoints
	 *
	 * @param AccessPoints $accessPoints
	 * @return AccessCode
	 */
	public function addAccessPoint(AccessPoints $accessPoints)
	{
		$this->accessPoints[] = $accessPoints;
		return $this;
	}

	/**
	 * Remove accessPoints
	 *
	 * @param AccessPoints $accessPoints
	 */
	public function removeAccessPoint(AccessPoints $accessPoints)
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

    /**
     * @return mixed
     */
    public function getLogotipo()
    {
        return $this->logotipo;
    }

    /**
     * @param mixed $logotipo
     */
    public function setLogotipo($logotipo)
    {
        $this->logotipo = $logotipo;
    }

    /**
     * @return mixed
     */
    public function getFileLogotipo()
    {
        return $this->fileLogotipo;
    }

    /**
     * @param mixed $fileLogotipo
     */
    public function setFileLogotipo($fileLogotipo)
    {
        $this->fileLogotipo = $fileLogotipo;
    }

    /**
     * @return mixed
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param mixed $backgroundColor
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * @return mixed
     */
    public function getFontColor()
    {
        return $this->fontColor;
    }

    /**
     * @param mixed $fontColor
     */
    public function setFontColor($fontColor)
    {
        $this->fontColor = $fontColor;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Add codes
     *
     * @param AccessCodeCodes $codes
     * @return AccessCode
     */
    public function addCode(AccessCodeCodes $codes)
    {
        $this->codes[] = $codes;
        return $this;
    }

    /**
     * Remove $codes
     *
     * @param \Wideti\DomainBundle\Entity\AccessCodeCodes $codes
     */
    public function removeCode(AccessCodeCodes $codes)
    {
        $this->codes->removeElement($codes);
    }

    /**
     * Get codes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCodes()
    {
        return $this->codes;
    }
}
