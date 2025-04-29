<?php

namespace Wideti\DomainBundle\Service\GuestPasswordRecovery;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\RecoveryGuestLocked;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class PasswordRecoveryManager implements PasswordRecoveryManagerInterface
{
    use EntityManagerAware;

    /**
     * @param Guest $guest
     * @return bool
     */
    public function recoveryIsLocked(Guest $guest)
    {
        $recoveryGuestLocked = $this->getRecoveryGuestLockedRepository()
            ->getRecoveryLockedByGuestId($guest->getMysql());
        if (!$this->recoveryPasswordIsLocked($recoveryGuestLocked)) return false;
        if ($this->lockedTimeIsLessThan10Minutes($recoveryGuestLocked)) return true;
        $this->unlockRecovery($recoveryGuestLocked);
        return false;
    }

    /**
     * @param Guest $guest
     * @return void
     */
    public function lockRecovery(Guest $guest)
    {
        if (!$this->guestRecoveryIsLocked($guest)){
            $recoveryGuestLocked = RecoveryGuestLocked::create($guest->getMysql());
            $this->getRecoveryGuestLockedRepository()
                ->save($recoveryGuestLocked);
        }
    }

    /**
     * @param Guest $guest
     * @return bool
     */
    private function guestRecoveryIsLocked(Guest $guest)
    {
        $recoveryGuestLocked = $this->getRecoveryGuestLockedRepository()
            ->getRecoveryLockedByGuestId($guest->getMysql());
        if (is_null($recoveryGuestLocked)) return false;
        return true;
    }

    /**
     * @param RecoveryGuestLocked $recoveryGuestLocked
     * @return void
     */
    private function unlockRecovery($recoveryGuestLocked)
    {
        $this->getRecoveryGuestLockedRepository()
            ->remove($recoveryGuestLocked);
    }

    /**
     * @param RecoveryGuestLocked $recoveryGuestLocked
     * @return bool
     */
    private function lockedTimeIsLessThan10Minutes($recoveryGuestLocked)
    {
        $lockedTime = $recoveryGuestLocked->getLockedAt()->diff(new \DateTime());
        return ($lockedTime->i <= 10);
    }

    /**
     * @param RecoveryGuestLocked $recoveryGuestLocked
     * @return bool
     */
    private function recoveryPasswordIsLocked($recoveryGuestLocked)
    {
        if (is_null($recoveryGuestLocked)) return false;
        return true;
    }

    private function getRecoveryGuestLockedRepository()
    {
        return $this->em->getRepository(RecoveryGuestLocked::class);
    }
}