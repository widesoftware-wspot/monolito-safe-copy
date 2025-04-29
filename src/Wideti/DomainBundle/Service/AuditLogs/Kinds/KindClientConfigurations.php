<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindClientConfigurations implements Kind
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
        return new KindClientConfigurations("client_configurations");
    }

    public function getValue()
    {
        return $this->value;
    }
}