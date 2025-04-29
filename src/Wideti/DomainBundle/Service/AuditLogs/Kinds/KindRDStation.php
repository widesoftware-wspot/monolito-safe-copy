<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindRDStation implements Kind
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
        return new KindRDStation("rd_station");
    }

    public function getValue()
    {
        return $this->value;
    }
}
