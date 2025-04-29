<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="contract_users")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ContractUserRepository")
 */
class ContractUser
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Contract", inversedBy="signatures")
     * @ORM\JoinColumn(name="contract_id", referencedColumnName="id")
     */
    protected $contract;

    /**
     * @ORM\ManyToOne(targetEntity="Users", inversedBy="contracts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     */
    protected $accept;

    /**
     * @ORM\Column(name="fingerprint", type="string", length=200)
     */
    protected $fingerprint;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set accept
     *
     * @param \DateTime $accept
     * @return ContractUser
     */
    public function setAccept($accept)
    {
        $this->accept = $accept;

        return $this;
    }

    /**
     * Get accept
     *
     * @return \DateTime
     */
    public function getAccept()
    {
        return $this->accept;
    }

    /**
     * Set contract
     *
     * @param \Wideti\DomainBundle\Entity\Contract $contract
     * @return ContractUser
     */
    public function setContract(\Wideti\DomainBundle\Entity\Contract $contract = null)
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * Get contract
     *
     * @return \Wideti\DomainBundle\Entity\Contract
     */
    public function getContract()
    {
        return $this->contract;
    }

    /**
     * Set user
     *
     * @param \Wideti\DomainBundle\Entity\Users $user
     * @return ContractUser
     */
    public function setUser(\Wideti\DomainBundle\Entity\Users $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Wideti\DomainBundle\Entity\Users
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setFingerprint($fingerprint)
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }
}