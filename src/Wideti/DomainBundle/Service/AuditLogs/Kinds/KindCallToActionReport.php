<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindCallToActionReport implements Kind
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
        return new KindCallToActionReport("call_to_action_report");
    }

    public function getValue()
    {
        return $this->value;
    }
}
