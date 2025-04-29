<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindTemplate implements Kind
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
        return new KindTemplate("template");
    }

    public function getValue()
    {
        return $this->value;
    }
}
