<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindAutomaticContracting implements Kind
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
        return new KindAutomaticContracting("automatic_contracting");
    }

    public function getValue()
    {
        return $this->value;
    }
}
