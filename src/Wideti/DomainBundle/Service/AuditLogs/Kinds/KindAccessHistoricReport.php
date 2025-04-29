<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindAccessHistoricReport implements Kind
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
        return new KindAccessHistoricReport("access_historic_report");
    }

    public function getValue()
    {
        return $this->value;
    }
}
