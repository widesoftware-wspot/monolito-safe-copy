<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="contracts")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ContractRepository")
 */
class Contract
{
    const SMS_COST = 1;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="type", type="integer")
     */
    protected $type;

    /**
     * @ORM\Column(name="text", type="text")
     */
    protected $text;

    /**
     * @ORM\OneToMany(targetEntity="ContractUser", mappedBy="contract")
     */
    private $signatures;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->signatures = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return Contract
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Add signatures
     *
     * @param \Wideti\DomainBundle\Entity\ContractUser $signatures
     * @return Contract
     */
    public function addSignature(\Wideti\DomainBundle\Entity\ContractUser $signatures)
    {
        $this->signatures[] = $signatures;

        return $this;
    }

    /**
     * Remove signatures
     *
     * @param \Wideti\DomainBundle\Entity\ContractUser $signatures
     */
    public function removeSignature(\Wideti\DomainBundle\Entity\ContractUser $signatures)
    {
        $this->signatures->removeElement($signatures);
    }

    /**
     * Get signatures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSignatures()
    {
        return $this->signatures;
    }
}
