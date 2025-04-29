<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\MaxDepth;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Google\Authenticator\GoogleAuthenticator;

/**
 * @ORM\Table(
 *      name="url_reset_password"
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\UrlResetPasswordRepository")
 */
class UrlResetPassword
{
      /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

     /**
     * @ORM\ManyToOne(targetEntity="Users", inversedBy="url_reset_password")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $user;

    /**
     * @ORM\Column(name="url", type="string", length=500)
     */
    private $url;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $createdAt;

      /**
     * @ORM\Column(name="expired_by_use", type="boolean")
     */
    private $expiredByUse;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\Wideti\DomainBundle\Entity\Users $user) {
        $this->user = $user;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }

    public function getExpiredByUse()
    {
        return $this->expiredByUse;
    }

    public function setExpiredByUse($expiredByUse) {
        $this->expiredByUse = $expiredByUse;
    }

    public function __toString()
    {
        return $this->url;
    }
}
