<?php


namespace Wideti\DomainBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="clients_legal_base")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ClientsLegalBaseRepository")
 */
class ClientsLegalBase
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="legalBases")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     */
    private $client;

    /**
     * @var integer
     * @ORM\Column(name="version", type="bigint", nullable=true)
     */
    private $version;

    /**
     * @var \DateTime
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;


    /**
     * @var LegalKinds
     * @ORM\ManyToOne(targetEntity="LegalKinds")
     * @ORM\JoinColumn(name="legal_kind", referencedColumnName="key", nullable=false)
     */
    private $legalKind;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getLegalKind()
    {
        return $this->legalKind;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param Client $client
     * @param LegalKinds $legalKind
     * @return ClientsLegalBase
     */
    public static function createActive(Client $client, LegalKinds $legalKind)
    {
        $clientLegalBase = new ClientsLegalBase();
        $now = new \DateTime();

        $clientLegalBase->client = $client;
        $clientLegalBase->legalKind = $legalKind;
        $clientLegalBase->version = $now->getTimestamp();
        $clientLegalBase->timestamp = $now;
        $clientLegalBase->active = true;
        return $clientLegalBase;
    }
}