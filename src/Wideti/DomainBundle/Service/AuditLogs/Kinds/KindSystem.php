<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindSystem implements Kind
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
        return new KindSystem("system");
    }

    public function getValue()
    {
        return $this->value;
    }
}
