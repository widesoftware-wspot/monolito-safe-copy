<?php


namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table(
 *      name="clients_controllers_unifi",
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="uk_clients_controllers_unifi_client_controllers_unifi",
 *              columns={
 *                  "unifi_id",
 *                  "client_id"
 *              }
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ClientsControllersUnifiRepository")
 */
class ClientsControllersUnifi
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
     * Type option
     * @ORM\Column(name="unifi_id", type="integer", nullable=false)
     */
    private $unifiId;

	/**
     * Type option
     * @ORM\Column(name="client_id", type="integer", nullable=false)
     */
    private $clientId;

	/**
	 * ClientsControllersUnifi constructor.
	 * @param $unifiId
	 * @param $clientId
	 */
	public function __construct($unifiId, $clientId)
	{
		$this->unifiId = $unifiId;
		$this->clientId = $clientId;
	}

    /**
     * @return int
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
	 * @return integer
	 */
	public function getUnifiId()
	{
		return $this->unifiId;
	}

	/**
	 * @return integer
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
     * @param $unifiId
     * @return $this
	 */
	public function setUnifiId($unifiId)
	{
		$this->unifiId = $unifiId;
	}

	/**
     * @param $clientId
     * @return $this
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}
}
