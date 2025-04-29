<?php


namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table(
 *      name="spots_users",
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="unique_spot_user",
 *              columns={
 *                  "user_id",
 *                  "client_id"
 *              }
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SpotUserRepository")
 */
class SpotUser
{
	/**
	 * @ORM\Column(name="user_id", type="integer")
	 * @ORM\Id()
	 * @var integer
	 */
	private $userId;

	/**
	 * @ORM\Column(name="client_id", type="integer")
	 * @ORM\Id()
	 * @var integer
	 */
	private $clientId;

	/**
	 * SpotUser constructor.
	 * @param $userId
	 * @param $clientId
	 */
	public function __construct($userId, $clientId)
	{
		$this->userId = $userId;
		$this->clientId = $clientId;
	}

	/**
	 * @return integer
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return integer
	 */
	public function getClientId()
	{
		return $this->clientId;
	}
}
