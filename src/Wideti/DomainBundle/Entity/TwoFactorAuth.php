<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="two_factor_auth")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\TwoFactorAuthRepository")
 */
class TwoFactorAuth
{
	const METHOD_POST = "POST";
	const METHOD_GET  = "GET";
	const METHOD_PUT  = "PUT";

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
	 * @ORM\Column(name="enable", type="boolean", options={"default"=1})
	 */
	private $enable = 1;

	/**
	 * @ORM\Column(name="shortcode", type="string", length=255)
	 */
	private $shortcode;

	/**
	 * @ORM\Column(name="endpoint", type="string", length=255)
	 */
	private $endpoint;

	/**
	 * @ORM\Column(name="method", type="string", length=10)
	 */
	private $method;

	/**
	 * @ORM\Column(name="field", type="string", length=55)
	 */
	private $field;

	/**
	 * @ORM\Column(name="http_headers", type="json_array", nullable=true)
	 */
	private $httpHeaders;

	/**
	 * @ORM\Column(name="message", type="json_array", nullable=true)
	 */
	private $message;

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
	public function isEnable()
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
	public function getShortcode()
	{
		return $this->shortcode;
	}

	/**
	 * @param mixed $shortcode
	 */
	public function setShortcode($shortcode)
	{
		$this->shortcode = $shortcode;
	}

	/**
	 * @return mixed
	 */
	public function getEndpoint()
	{
		return $this->endpoint;
	}

	/**
	 * @param mixed $endpoint
	 */
	public function setEndpoint($endpoint)
	{
		$this->endpoint = $endpoint;
	}

	/**
	 * @return mixed
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param mixed $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @return mixed
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * @param mixed $field
	 */
	public function setField($field)
	{
		$this->field = $field;
	}

	/**
	 * @return mixed
	 */
	public function getHttpHeaders()
	{
		return $this->httpHeaders;
	}

	/**
	 * @param mixed $httpHeaders
	 */
	public function setHttpHeaders(array $httpHeaders = [])
	{
		$this->httpHeaders = $httpHeaders;
	}

	/**
	 * @return mixed
	 */
	public function getMessage()
	{
		return $this->message ? $this->message[0] : null;
	}

	/**
	 * @param mixed $message
	 */
	public function setMessage(array $message = [])
	{
		$this->message = $message;
	}
}
