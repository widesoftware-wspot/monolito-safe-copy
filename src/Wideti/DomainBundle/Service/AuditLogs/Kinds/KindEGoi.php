<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindEGoi implements Kind
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
        return new KindEGoi("e_goi");
    }

    public function getValue()
    {
        return $this->value;
    }
}
