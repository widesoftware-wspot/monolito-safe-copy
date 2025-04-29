<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="audit_log",
 *     indexes={
 *         @ORM\Index(name="source_id_idx", columns={"source_id"}),
 *         @ORM\Index(name="client_id_idx", columns={"client_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AuditLogRepository")
 */
class AuditLog
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="client_id", type="integer")
     */
    protected $clientId;

    /**
     * @ORM\Column(name="source_id", type="integer", nullable=true)
     */
    protected $sourceId;

    /**
     * @ORM\Column(name="source_username", type="string", length=100)
     */
    protected $sourceUsername;

    /**
     * @ORM\Column(name="event_type", type="string", length=255)
     */
    private $eventType;

    /**
     * @ORM\Column(name="target_kind", type="string", length=255)
     */
    private $targetKind;

    /**
     * @ORM\Column(name="target_id", type="string",length=255, nullable=true)
     */
    private $targetId;

    /**
     * @ORM\Column(name="target_identifier", type="string", length=255, nullable=true)
     */
    private $targetIdentifier;

    /**
    * @ORM\Column(name="changes", type="json_array", nullable=true)
    */
    private $changes;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;


    // Getters and Setters

    public function getId()
    {
        return $this->id;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }

    public function getSourceUsername()
    {
        return $this->sourceUsername;
    }

    public function setSourceUsername($sourceUsername)
    {
        $this->sourceUsername = $sourceUsername;
    }

    public function getTargetKind()
    {
        return $this->targetKind;
    }

    public function setTargetKind($targetKind)
    {
        $this->targetKind = $targetKind;
    }

    public function getTargetId()
    {
        return $this->targetId;
    }

    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
    }

    public function getTargetIdentifier()
    {
        return $this->targetIdentifier;
    }

    public function setTargetIdentifier($targetIdentifier)
    {
        $this->targetIdentifier = $targetIdentifier;
    }

    public function getEventType()
    {
        return $this->eventType;
    }

    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function setChanges($changes)
    {
        $this->changes = $changes;
    }
}
