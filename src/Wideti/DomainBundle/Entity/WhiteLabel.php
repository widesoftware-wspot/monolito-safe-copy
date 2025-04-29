<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="white_label")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\WhiteLabelRepository")
 */
class WhiteLabel
{
	use TimestampableEmbed;

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
	 * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
	 */
	private $companyName;

	/**
	 * @ORM\Column(name="panel_color", type="string", length=15, nullable=true)
	 */
	private $panelColor;

	/**
	 * @ORM\Column(name="logotipo", type="string", length=255, nullable=true)
	 */
	private $logotipo;

	/**
	 * @Assert\Image(maxWidth = 250, maxHeight = 250)
	 */
	private $fileLogotipo;

	/**
	 * @ORM\Column(name="signature", type="json_array", nullable=true)
	 */
	private $signature;

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
	public function getCompanyName()
	{
		return $this->companyName;
	}

	/**
	 * @param mixed $companyName
	 */
	public function setCompanyName($companyName)
	{
		$this->companyName = $companyName;
	}

	/**
	 * @return mixed
	 */
	public function getPanelColor()
	{
		return $this->panelColor;
	}

	/**
	 * @param mixed $panelColor
	 */
	public function setPanelColor($panelColor)
	{
		$this->panelColor = $panelColor;
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
		if ($logotipo != null) {
			$this->logotipo = $logotipo;
		}
	}

	/**
	 * @return mixed
	 */
	public function getFileLogotipo()
	{
		return $this->fileLogotipo;
	}

	/**
	 * @param $fileLogotipo
	 */
	public function setFileLogotipo($fileLogotipo)
	{
		$this->fileLogotipo = $fileLogotipo;
	}

	/**
	 * @return mixed
	 */
	public function getSignature()
	{
		return $this->signature;
	}

	/**
	 * @param mixed $signature
	 */
	public function setSignature($signature)
	{
		$this->signature = $signature;
	}

    public function toArray()
    {
        $whiteLabel = [];
        $whiteLabel['client_id'] = $this->getClient()->getId();
        $whiteLabel['company_name'] = $this->getCompanyName();
        $whiteLabel['panel_color'] = $this->getPanelColor();
        $whiteLabel['logotipo'] = $this->getLogotipo();
        $whiteLabel['signature'] = $this->getSignature();
        return $whiteLabel;
    }
}
