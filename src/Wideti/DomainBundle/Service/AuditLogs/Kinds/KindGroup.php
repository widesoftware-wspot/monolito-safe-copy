<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindGroup implements Kind
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
        return new KindGroup("group");
    }

    public function getValue()
    {
        return $this->value;
    }
}
