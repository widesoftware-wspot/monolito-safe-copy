<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindClient implements Kind
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
        return new KindClient("client");
    }

    public function getValue()
    {
        return $this->value;
    }
}
