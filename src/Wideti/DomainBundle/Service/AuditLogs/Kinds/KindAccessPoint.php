<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindAccessPoint implements Kind
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
        return new KindAccessPoint("access_point");
    }

    public function getValue()
    {
        return $this->value;
    }
}
