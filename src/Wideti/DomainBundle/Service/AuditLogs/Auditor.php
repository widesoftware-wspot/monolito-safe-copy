<?php


namespace Wideti\DomainBundle\Service\AuditLogs;


interface Auditor
{
    /**
     * @return AuditEvent
     */
    public function newEvent();
    public function push(AuditEvent $event);
}