<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="campaign_media_image")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignMediaImageRepository")
 */
class CampaignMediaImage
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
     * @Assert\NotBlank(
     *      groups={"exhibitionTimeNotBlank"},
     *      message = "Este campo deve ser preenchido"
     * )
     * @Assert\Length(
     *      groups={"exhibitionTime"},
     *      max = "2",
     *      maxMessage = "O tempo de exibição deve ter no máximo {{ limit }} caracteres"
     * )
     * @ORM\Column(name="exhibition_time", type="integer", nullable=true)
     */
    private $exhibitionTime;

    /**
     * @ORM\Column(name="full_size", type="boolean", options={"default":0} )
     */
    private $fullSize;

    /**

     * @ORM\Column(name="image_desktop", type="string", length=100, nullable=true)
     */
    private $imageDesktop;

        /**

     * @ORM\Column(name="image_desktop2", type="string", length=100, nullable=true)
     */
    private $imageDesktop2;

        /**

     * @ORM\Column(name="image_desktop3", type="string", length=100, nullable=true)
     */
    private $imageDesktop3;

    /**
     * @Assert\NotBlank(
     *      groups={"imageNotBlank"},
     *      message = "É necessário selecionar uma imagem para o Banner Vertical"
     * )
     * @ORM\Column(name="image_mobile", type="string", length=100, nullable=true)
     */
    private $imageMobile;

        /**
     * @ORM\Column(name="image_mobile2", type="string", length=100, nullable=true)
     */
    private $imageMobile2;

        /**
     * @ORM\Column(name="image_mobile3", type="string", length=100, nullable=true)
     */
    private $imageMobile3;

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
    public function getExhibitionTime()
    {
        return $this->exhibitionTime;
    }

    /**
     * @param mixed $exhibitionTime
     */
    public function setExhibitionTime($exhibitionTime)
    {
        $this->exhibitionTime = $exhibitionTime;
    }

    /**
     * @return mixed
     */
    public function getFullSize()
    {
        return $this->fullSize;
    }

    /**
     * @param mixed $fullSize
     */
    public function setFullSize($fullSize)
    {
        $this->fullSize = $fullSize;
    }

    /**
     * @return mixed
     */
    public function getImageDesktop()
    {
        // Se a imagem desktop for null, retorna a imagem mobile
       return $this->imageDesktop !== null ? $this->imageDesktop : $this->imageMobile;
    }

        /**
     * @return mixed
     */
    public function getImageDesktop2()
    {


        // Se a imagem desktop2 for null, retorna a imagem mobile2, caso contrário retorna desktop2
        return $this->imageDesktop2 !== null ? $this->imageDesktop2 : $this->imageMobile2;

    }
    /**
     * @return mixed
     */
    public function getImageDesktop3()
    {

        if ($this->imageDesktop3 !== null) {
            return $this->imageDesktop3;
        } else {
            return $this->imageMobile3;
        }
    }
    /**
     * @param mixed $imageDesktop
     */
    public function setImageDesktop($imageDesktop)
    {
        if ($imageDesktop != null) {
            $this->imageDesktop = $imageDesktop;
        }
    }

        /**
     * @param mixed $imageDesktop2
     */
    public function setImageDesktop2($imageDesktop2)
    {
        if ($imageDesktop2 != null) {
            $this->imageDesktop2 = $imageDesktop2;
        }
    }

        /**
     * @param mixed $imageDesktop3
     */
    public function setImageDesktop3($imageDesktop3)
    {
        if ($imageDesktop3 != null) {
            $this->imageDesktop3 = $imageDesktop3;
        }
    }

    public function setImageDesktopIsNull()
    {
        $this->imageDesktop = null;
    }

    /**
     * @return mixed
     */
    public function getImageMobile()
    {
        return $this->imageMobile;
    }

        /**
     * @return mixed
     */
    public function getImageMobile2()
    {
        return $this->imageMobile2;
    }

          /**
     * @return mixed
     */
    public function getImageMobile3()
    {
        return $this->imageMobile3;
    }


    /**
     * @param mixed $imageMobile
     */
    public function setImageMobile($imageMobile)
    {
        if ($imageMobile != null) {
            $this->imageMobile = $imageMobile;
        }
    }

        /**
     * @param mixed $imageMobile2
     */
    public function setImageMobile2($imageMobile2)
    {
        if ($imageMobile2!= null) {
            $this->imageMobile2 = $imageMobile2;
        }
    }

        /**
     * @param mixed $imageMobile3
     */
    public function setImageMobile3($imageMobile3)
    {
        if ($imageMobile3 != null) {
            $this->imageMobile3 = $imageMobile3;
        }
    }

    public function setImageMobileIsNull()
    {
        $this->imageMobile = null;
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
     * Conta o número de imagens mobile não nulas.
     *
     * @return int Número total de imagens mobile.
     */
    public function getTotalMobileImages()
    {
        $count = 0;

        if ($this->imageMobile) $count++;
        if ($this->imageMobile2) $count++;
        if ($this->imageMobile3) $count++;

        return $count;
    }

        /**
     * Conta o número de imagens mobile não nulas.
     *
     * @return int Número total de imagens mobile.
     */
    public function getTotalDesktopImages()
    {
        $count = 0;

        if ($this->imageDesktop) $count++;
        if ($this->imageDesktop2) $count++;
        if ($this->imageDesktop3) $count++;

        return $count;
    }
}
