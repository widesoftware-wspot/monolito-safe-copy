<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindConfigurationValue implements Kind
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
        return new KindConfigurationValue("configuration_value");
    }

    public function getValue()
    {
        return $this->value;
    }
}