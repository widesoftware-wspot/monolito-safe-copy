<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindDeviceLock implements Kind
{
    private $value;

    private function __construct($value) {
        $this->value = $value;
    }

    /**
     * @return Kind
     */
    public static function kind()
    {
        return new KindDeviceLock("device_lock");
    }

    public function getValue()
    {
        return $this->value;
    }
}
