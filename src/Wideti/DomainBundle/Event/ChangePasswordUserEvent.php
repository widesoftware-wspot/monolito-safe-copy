<?php

namespace Wideti\DomainBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Wideti\DomainBundle\Entity\Users;

class ChangePasswordUserEvent extends Event
{
    /**
     * @var Users
     */
    protected $user;

    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
