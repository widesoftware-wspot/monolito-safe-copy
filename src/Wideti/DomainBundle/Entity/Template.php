<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\DomainBundle\Validator\Constraints as CustomAssert;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="template")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\TemplateRepository")
 */
class Template
{
    use TimestampableEmbed;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(name="partner_logo", type="string", length=100, nullable=true)     *
     */
    private $partnerLogo;

    /**
     * @Assert\Image(maxWidth = 250,maxHeight = 250)
     */
    private $filePartnerLogo;

    /**
     * @ORM\Column(name="background_color", type="string", length=7, nullable=true)
     */
    private $backgroundColor;

    /**
     * @ORM\Column(name="background_image", type="string", length=100, nullable=true)
     */
    private $backgroundImage;

	/**
	 * @ORM\Column(name="background_image_hash", type="string", length=45, nullable=true)
	 */
	private $backgroundImageHash;

	/**
	 * @ORM\Column(name="background_portrait_image", type="string", length=100, nullable=true)
	 */
	private $backgroundPortraitImage;

	/**
	 * @ORM\Column(name="background_portrait_image_hash", type="string", length=45, nullable=true)
	 */
	private $backgroundPortraitImageHash;

    /**
     * @Assert\Image(
     *     maxSize = "2M",
     *     mimeTypes = {"image/jpg", "image/png", "image/jpeg"},
     *     mimeTypesMessage = "Somente são aceitos arquivos com extensões: .jpg .jpeg .png")
     *     allowPortrait=false
     * )
     * @CustomAssert\Horizontal16p9()
     */
    private $fileBackgroundImage;

    /**
     * @Assert\Image(
     *     maxSize = "2M",
     *     mimeTypes = {"image/jpg", "image/png", "image/jpeg"},
     *     mimeTypesMessage = "Somente são aceitos arquivos com extensões: .jpg .jpeg .png")
     *     allowLandscape=false
     * )
     * @CustomAssert\Vertical9p16()
     */
    private $fileBackgroundPortraitImage;

    /**
     * @ORM\Column(name="background_repeat", type="string", length=50, nullable=true)
     */
    private $backgroundRepeat;

    /**
     * @ORM\Column(name="background_position_x", type="string", length=50, nullable=true)
     */
    private $backgroundPositionX;

    /**
     * @ORM\Column(name="background_position_y", type="string", length=50, nullable=true)
     */
    private $backgroundPositionY;

    /**
     * @ORM\Column(name="font_color", type="string", length=7, nullable=true)
     */
    private $fontColor;

    /**
     * @ORM\Column(name="box_opacity", type="boolean", options={"default":0})
     */
    private $boxOpacity = true;

    /**
     * @ORM\Column(name="login_box_color", type="string", length=7, nullable=true)
     */
    private $loginBoxColor = "#ec213a";

    /**
     * @ORM\Column(name="login_font_color", type="string", length=7, nullable=true)
     */
    private $loginFontColor = "#ffffff";

    /**
     * @ORM\Column(name="login_button_color", type="string", length=7, nullable=true)
     */
    private $loginButtonColor = "#424242";

    /**
     * @ORM\Column(name="login_button_font_color", type="string", length=7, nullable=true)
     */
    private $loginButtonFontColor = "#FFFFFF";

    /**
     * @ORM\Column(name="signup_box_color", type="string", length=7, nullable=true)
     */
    private $signupBoxColor = "#424242";

    /**
     * @ORM\Column(name="signup_font_color", type="string", length=7, nullable=true)
     */
    private $signupFontColor = "#FFFFFF";

    /**
     * @ORM\Column(name="signup_button_color", type="string", length=7, nullable=true)
     */
    private $signupButtonColor = "#ec213a";

    /**
     * @ORM\Column(name="signup_button_font_color", type="string", length=7, nullable=true)
     */
    private $signupButtonFontColor = "#FFFFFF";

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\AccessPointsGroups", mappedBy="template")
     */
    protected $accessPointsGroups;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\AccessPoints", mappedBy="template")
     */
    protected $accessPoints;

    /**
     * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *
     */
    protected $client;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\Campaign", mappedBy="template")
     */
    protected $campaign;

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
    public function getPartnerLogo()
    {
        return $this->partnerLogo;
    }

    /**
     * @param mixed $partnerLogo
     */
    public function setPartnerLogo($partnerLogo)
    {
        if ($partnerLogo != null) {
            $this->partnerLogo = $partnerLogo;
        }
    }

    public function setPartnerLogoIsNull()
    {
        $this->partnerLogo = null;
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
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param mixed $backgroundImage
     */
    public function setBackgroundImage($backgroundImage)
    {
        if ($backgroundImage != null) {
            $this->backgroundImage = $backgroundImage;
        }
    }

	/**
	 * @return mixed
	 */
	public function getBackgroundImageHash()
	{
		return $this->backgroundImageHash ? "_{$this->backgroundImageHash}" : null;
	}

	/**
	 * @param mixed $backgroundImageHash
	 */
	public function setBackgroundImageHash($backgroundImageHash)
	{
		$this->backgroundImageHash = $backgroundImageHash;
	}

    /**
     * @return mixed
     */
    public function getBackgroundPortraitImage()
    {
        return $this->backgroundPortraitImage;
    }

    /**
     * @param mixed $backgroundPortraitImage
     */
    public function setBackgroundPortraitImage($backgroundPortraitImage)
    {
        if ($backgroundPortraitImage) {
            $this->backgroundPortraitImage = $backgroundPortraitImage;
        }
    }

	/**
	 * @return mixed
	 */
	public function getBackgroundPortraitImageHash()
	{
		return $this->backgroundPortraitImageHash ? "_{$this->backgroundPortraitImageHash}" : null;
	}

	/**
	 * @param mixed $backgroundPortraitImageHash
	 */
	public function setBackgroundPortraitImageHash($backgroundPortraitImageHash)
	{
		$this->backgroundPortraitImageHash = $backgroundPortraitImageHash;
	}

    public function setBackgroundImageIsNull()
    {
        $this->backgroundImage = null;
    }

    public function setBackgroundPortraitImageIsNull()
    {
        $this->backgroundPortraitImage = null;
    }

    /**
     * @return mixed
     */
    public function getBackgroundRepeat()
    {
        return $this->backgroundRepeat;
    }

    /**
     * @param mixed $backgroundRepeat
     */
    public function setBackgroundRepeat($backgroundRepeat)
    {
        $this->backgroundRepeat = $backgroundRepeat;
    }

    /**
     * @return mixed
     */
    public function getBackgroundPositionX()
    {
        return $this->backgroundPositionX;
    }

    /**
     * @param mixed $backgroundPositionX
     */
    public function setBackgroundPositionX($backgroundPositionX)
    {
        $this->backgroundPositionX = $backgroundPositionX;
    }

    /**
     * @return mixed
     */
    public function getBackgroundPositionY()
    {
        return $this->backgroundPositionY;
    }

    /**
     * @param mixed $backgroundPositionY
     */
    public function setBackgroundPositionY($backgroundPositionY)
    {
        $this->backgroundPositionY = $backgroundPositionY;
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
    public function getBoxOpacity()
    {
        return $this->boxOpacity;
    }

    /**
     * @param mixed $boxOpacity
     */
    public function setBoxOpacity($boxOpacity)
    {
        $this->boxOpacity = $boxOpacity;
    }

    /**
     * @return mixed
     */
    public function getLoginBoxColor()
    {
        return $this->loginBoxColor;
    }

    /**
     * @param mixed $loginBoxColor
     */
    public function setLoginBoxColor($loginBoxColor)
    {
        $this->loginBoxColor = $loginBoxColor;
    }

    /**
     * @return mixed
     */
    public function getLoginFontColor()
    {
        return $this->loginFontColor;
    }

    /**
     * @param mixed $loginFontColor
     */
    public function setLoginFontColor($loginFontColor)
    {
        $this->loginFontColor = $loginFontColor;
    }

    /**
     * @return mixed
     */
    public function getLoginButtonColor()
    {
        return $this->loginButtonColor;
    }

    /**
     * @param mixed $loginButtonColor
     */
    public function setLoginButtonColor($loginButtonColor)
    {
        $this->loginButtonColor = $loginButtonColor;
    }

    /**
     * @return mixed
     */
    public function getLoginButtonFontColor()
    {
        return $this->loginButtonFontColor;
    }

    /**
     * @param mixed $loginButtonFontColor
     */
    public function setLoginButtonFontColor($loginButtonFontColor)
    {
        $this->loginButtonFontColor = $loginButtonFontColor;
    }

    /**
     * @return mixed
     */
    public function getSignupBoxColor()
    {
        return $this->signupBoxColor;
    }

    /**
     * @param mixed $signupBoxColor
     */
    public function setSignupBoxColor($signupBoxColor)
    {
        $this->signupBoxColor = $signupBoxColor;
    }

    /**
     * @return mixed
     */
    public function getSignupFontColor()
    {
        return $this->signupFontColor;
    }

    /**
     * @param mixed $signupFontColor
     */
    public function setSignupFontColor($signupFontColor)
    {
        $this->signupFontColor = $signupFontColor;
    }

    /**
     * @return mixed
     */
    public function getSignupButtonColor()
    {
        return $this->signupButtonColor;
    }

    /**
     * @param mixed $signupButtonColor
     */
    public function setSignupButtonColor($signupButtonColor)
    {
        $this->signupButtonColor = $signupButtonColor;
    }

    /**
     * @return mixed
     */
    public function getSignupButtonFontColor()
    {
        return $this->signupButtonFontColor;
    }

    /**
     * @param mixed $signupButtonFontColor
     */
    public function setSignupButtonFontColor($signupButtonFontColor)
    {
        $this->signupButtonFontColor = $signupButtonFontColor;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accessPointsGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->campaign = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add accessPointsGroups
     *
     * @param \Wideti\DomainBundle\Entity\AccessPointsGroups $accessPointsGroups
     * @return Template
     */
    public function addAccessPointsGroup(\Wideti\DomainBundle\Entity\AccessPointsGroups $accessPointsGroups)
    {
        $this->accessPointsGroups[] = $accessPointsGroups;

        return $this;
    }

    /**
     * Remove accessPointsGroups
     *
     * @param \Wideti\DomainBundle\Entity\AccessPointsGroups $accessPointsGroups
     */
    public function removeAccessPointsGroup(\Wideti\DomainBundle\Entity\AccessPointsGroups $accessPointsGroups)
    {
        $this->accessPointsGroups->removeElement($accessPointsGroups);
    }

    /**
     * Get accessPointsGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccessPointsGroups()
    {
        return $this->accessPointsGroups;
    }

    public function addAccessPoints(\Wideti\DomainBundle\Entity\AccessPoints $accessPoints)
    {
        $this->accessPoints[] = $accessPoints;

        return $this;
    }

    public function removeAccessPoints(\Wideti\DomainBundle\Entity\AccessPoints $accessPoints)
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
     * Add campaign
     *
     * @param \Wideti\DomainBundle\Entity\Campaign $campaign
     * @return Template
     */
    public function addCampaign(\Wideti\DomainBundle\Entity\Campaign $campaign)
    {
        $this->campaign[] = $campaign;

        return $this;
    }

    /**
     * Remove campaign
     *
     * @param \Wideti\DomainBundle\Entity\Campaign $campaign
     */
    public function removeCampaign(\Wideti\DomainBundle\Entity\Campaign $campaign)
    {
        $this->campaign->removeElement($campaign);
    }

    /**
     * Get campaign
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->name;
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
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getFilePartnerLogo()
    {
        return $this->filePartnerLogo;
    }

    /**
     * @param mixed $filePartnerLogo
     */
    public function setFilePartnerLogo($filePartnerLogo)
    {
        $this->filePartnerLogo = $filePartnerLogo;
    }

    /**
     * @return mixed
     */
    public function getFileBackgroundImage()
    {
        return $this->fileBackgroundImage;
    }

    /**
     * @param mixed $fileBackgroundImage
     */
    public function setFileBackgroundImage($fileBackgroundImage)
    {
        $this->fileBackgroundImage = $fileBackgroundImage;
    }

    /**
     * @return mixed
     */
    public function getFileBackgroundPortraitImage()
    {
        return $this->fileBackgroundPortraitImage;
    }

    /**
     * @param $fileBackgroundPortraitImage
     */
    public function setFileBackgroundPortraitImage($fileBackgroundPortraitImage)
    {
        $this->fileBackgroundPortraitImage = $fileBackgroundPortraitImage;
    }

    /**
     * @return array
     */
    public function getBackgroundCSSConfiguration()
    {
        $imageString    = "template_{$this->client->getDomain()}_{$this->id}";
        $landscapeHash  = $this->getBackgroundImageHash();
        $portraitHash   = $this->getBackgroundPortraitImageHash();
        $styles         = [];

        if ($this->getBackgroundPortraitImage()) {
            $portraitExtension = explode('.', $this->getBackgroundPortraitImage());
            $portraitExtension = $portraitExtension[sizeof($portraitExtension) - 1];

            $styles[] = [
                [
                    'maxWidth'    => '1080px',
                    'imageName'   => "{$imageString}{$portraitHash}_vertical_100.{$portraitExtension}",
                    'orientation' => 'portrait'
                ],
                [
                    'maxWidth'    => '864px',
                    'imageName'   => "{$imageString}{$portraitHash}_vertical_80.{$portraitExtension}",
                    'orientation' => 'portrait'
                ],
                [
                    'maxWidth'    => '648px',
                    'imageName'   => "{$imageString}{$portraitHash}_vertical_60.{$portraitExtension}",
                    'orientation' => 'portrait'
                ],
                [
                    'maxWidth'    => '432px',
                    'imageName'   => "{$imageString}{$portraitHash}_vertical_40.{$portraitExtension}",
                    'orientation' => 'portrait'
                ]
            ];
        }

        if ($this->getBackgroundImage()) {
            $landscapeExtension = explode('.', $this->getBackgroundImage());
            $landscapeExtension = $landscapeExtension[sizeof($landscapeExtension) - 1];

            $styles[] = [
                [
                'maxWidth'    => '1400px',
                'imageName'   => "{$imageString}{$landscapeHash}_horizontal_100.{$landscapeExtension}",
                'orientation' => 'landscape'
                ],
                [
                    'maxWidth'    => '1120px',
                    'imageName'   => "{$imageString}{$landscapeHash}_horizontal_80.{$landscapeExtension}",
                    'orientation' => 'landscape'
                ],
                [
                    'maxWidth'    => '840px',
                    'imageName'   => "{$imageString}{$landscapeHash}_horizontal_60.{$landscapeExtension}",
                    'orientation' => 'landscape'
                ],
                [
                    'maxWidth'    => '560px',
                    'imageName'   => "{$imageString}{$landscapeHash}_horizontal_40.{$landscapeExtension}",
                    'orientation' => 'landscape'
                ]
            ];
        }

        return $styles;
    }

    public function getUpdatedTimestamp()
    {
    	return $this->getUpdated()->getTimestamp();
    }
}