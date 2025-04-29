<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindAccessPointGroup implements Kind
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
        return new KindAccessPointGroup("access_point_group");
    }

    public function getValue()
    {
        return $this->value;
    }
}
