<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="recovery_guest_locked")
 * @ORM\Table(indexes={@ORM\Index(name="idx_guest_id", columns={"guest_id"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\RecoveryGuestLockedRepository")
 */
class RecoveryGuestLocked
{
    /**
     * @ORM\Column(name="guest_id", type="integer")
     * @ORM\Id()
     */
    private $guestId;

    /**
     * @ORM\Column(name="locked_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $lockedAt;

    public function getGuestId()
    {
        return $this->guestId;
    }

    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    public static function create($guestId)
    {
        $obj = new RecoveryGuestLocked();
        $obj->guestId  = $guestId;
        $obj->lockedAt = new \DateTime();
        return $obj;
    }
}