<?php
namespace Wideti\DomainBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Wideti\DomainBundle\Document\Guest\Guest;

class AuthenticationEvent extends Event
{
    /**
     * @var Guest
     */
    protected $guest;
    protected $method;

    public function __construct(Guest $guest, $method)
    {
        $this->guest  = $guest;
        $this->method = $method;
    }

    public function getGuest()
    {
        return $this->guest;
    }

    public function getMethod()
    {
        return $this->method;
    }
}
