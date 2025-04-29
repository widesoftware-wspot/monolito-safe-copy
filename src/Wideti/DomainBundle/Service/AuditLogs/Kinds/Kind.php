<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


interface Kind
{
    /**
     * @return Kind
     */
    public static function kind();

    /**
     * @return string
     */
    public function getValue();
}
