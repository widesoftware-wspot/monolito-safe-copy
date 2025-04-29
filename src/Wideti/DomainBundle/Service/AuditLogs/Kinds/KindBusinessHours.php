<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindBusinessHours implements Kind
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
        return new KindBusinessHours("business_hours");
    }

    public function getValue()
    {
        return $this->value;
    }
}
