<?php

namespace Wideti\DomainBundle\Listener\Exception\Handlers;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

interface Handler
{
    public function getResponse(GetResponseForExceptionEvent $event);
}