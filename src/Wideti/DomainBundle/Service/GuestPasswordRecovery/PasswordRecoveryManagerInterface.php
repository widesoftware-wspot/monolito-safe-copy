<?php

namespace Wideti\DomainBundle\Service\GuestPasswordRecovery;

use Wideti\DomainBundle\Document\Guest\Guest;

interface PasswordRecoveryManagerInterface
{
    public function recoveryIsLocked(Guest $guest);
    public function lockRecovery(Guest $guest);
}