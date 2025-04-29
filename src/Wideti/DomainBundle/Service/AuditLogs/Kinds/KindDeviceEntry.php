<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindDeviceEntry implements Kind
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
        return new KindDeviceEntry("device_entry");
    }

    public function getValue()
    {
        return $this->value;
    }
}
