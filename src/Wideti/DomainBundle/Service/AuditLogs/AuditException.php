<?php


namespace Wideti\DomainBundle\Service\AuditLogs;


class AuditException extends \Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}