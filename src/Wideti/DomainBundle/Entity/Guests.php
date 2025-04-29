<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Wideti\DomainBundle\Validator\Constraints as MyAssert;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="visitantes")
 *
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\GuestsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Guests
{
    const STATUS_INACTIVE           = 0;
    const STATUS_ACTIVE             = 1;
    const STATUS_PENDING_APPROVAL   = 2;
    const STATUS_BLOCKED            = 3;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $client;

    /**
     * @ORM\OneToMany(targetEntity="Radcheck", mappedBy="guest", fetch="EXTRA_LAZY")
     * @Exclude()
     */
    protected $radcheck;

    /**
     * @Assert\NotBlank(groups={"frontend"})
     * @Assert\Length(
     *      groups={"frontend", "admin"},
     *      min=6,
     *      max=16,
     *      minMessage = "A senha deve ter ao menos 6 caracteres",
     *      maxMessage = "A senha deve ter no máximo 16 caracteres",
     * )
     */
    private $password;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->radcheck = new ArrayCollection();
        $this->acessos  = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
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

    /**
     * Add radcheck
     *
     * @param  \Wideti\DomainBundle\Entity\Radcheck $radcheck
     * @return Guests
     */
    public function addRadcheck(\Wideti\DomainBundle\Entity\Radcheck $radcheck)
    {
        $this->radcheck[] = $radcheck;

        return $this;
    }

    /**
     * Remove radcheck
     *
     * @param \Wideti\DomainBundle\Entity\Radcheck $radcheck
     */
    public function removeRadcheck(\Wideti\DomainBundle\Entity\Radcheck $radcheck)
    {
        $this->radcheck->removeElement($radcheck);
    }

    /**
     * Get radcheck
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRadcheck()
    {
        return $this->radcheck;
    }

    public function getAcessos()
    {
        return $this->acessos;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function __toString()
    {
        return (string) "Client: ".$this->client->getId() .". MysqlId: ".$this->getId();
    }


}
