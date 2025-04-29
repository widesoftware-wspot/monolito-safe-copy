<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindConfiguration implements Kind
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
        return new KindConfiguration("configuration");
    }

    public function getValue()
    {
        return $this->value;
    }
}