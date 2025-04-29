<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindGuestListReport implements Kind
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
        return new KindGuestListReport("guest_list_report");
    }

    public function getValue()
    {
        return $this->value;
    }
}
