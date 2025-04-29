<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindGuest implements Kind
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
        return new KindGuest("guest");
    }

    public function getValue()
    {
        return $this->value;
    }
}
