<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindAccessCode implements Kind
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
        return new KindAccessCode("access_code");
    }

    public function getValue()
    {
        return $this->value;
    }
}
