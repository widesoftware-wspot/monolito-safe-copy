<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="smartlocation_credentials")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SmartLocationRepository")
 */
class SmartLocationCredentials
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 */
	protected $client;

	/**
	 * @ORM\Column(name="account_name", type="string", length=80, nullable=false)
	 */
	private $accountName;

	/**
	 * @ORM\Column(name="customer_id", type="string", length=50, nullable=false)
	 */
	private $customerId;

	/**
	 * @ORM\Column(name="password", type="string", length=120, nullable=false)
	 */
	private $password;

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
	 */
	public function setClient(\Wideti\DomainBundle\Entity\Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @return mixed
	 */
	public function getAccountName()
	{
		return $this->accountName;
	}

	/**
	 * @param mixed $accountName
	 */
	public function setAccountName($accountName)
	{
		$this->accountName = $accountName;
	}

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
	public function setPassword($password)
    {
        $this->password = $password;
    }
}
