<?php


namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_token_auth")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\UsersTokensAuthRepository")
 */
class UsersTokensAuth
{
    /**
     * @ORM\OneToOne(targetEntity="Users", mappedBy="userTokenAuth")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @ORM\Id()
     */
    private $user;

    /**
     * @ORM\Column(name="token", type="text")
     */
    private $token;

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    public static function build(Users $user)
    {
        $userToken = new UsersTokensAuth();
        $userToken->user = $user;
        return $userToken;
    }
}