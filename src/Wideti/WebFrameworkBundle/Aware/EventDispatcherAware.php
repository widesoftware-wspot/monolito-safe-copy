<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Symfony Server Setup: - [ setEventDispatcher, ["@event_dispatcher"] ]
 */
trait EventDispatcherAware
{
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
