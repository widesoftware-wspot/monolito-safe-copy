<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindGuestsReport implements Kind
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
        return new KindGuestsReport("guests_report");
    }

    public function getValue()
    {
        return $this->value;
    }
}
