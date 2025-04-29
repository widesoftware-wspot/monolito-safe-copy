<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="radpostauth")
 * @ORM\Entity()
 */
class Radpostauth
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="username", type="string", length=64, nullable=false)
     */
    private $username;

    /**
     * @ORM\Column(name="pass", type="string", length=64, nullable=false)
     */
    private $pass;

    /**
     * @ORM\Column(name="reply", type="string", length=32, nullable=false)
     */
    private $reply;

    /**
     * @ORM\Column(name="authdate", type="datetime", nullable=false)
     */
    private $authdate;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    public function getReply()
    {
        return $this->reply;
    }

    public function setReply($reply)
    {
        $this->reply = $reply;
    }

    public function getAuthdate()
    {
        return $this->authdate;
    }

    public function setAuthdate($authdate)
    {
        $this->authdate = $authdate;
    }
}
