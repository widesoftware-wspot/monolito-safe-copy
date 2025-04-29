<?php

namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;

interface EventType
{
    /**
     * @return EventType
     */
    public static function event();

    /**
     * @return string
     */
    public function getValue();
}