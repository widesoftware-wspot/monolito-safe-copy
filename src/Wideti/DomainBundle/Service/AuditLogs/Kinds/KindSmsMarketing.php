<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindSmsMarketing implements Kind
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
        return new KindSmsMarketing("sms_marketing");
    }

    public function getValue()
    {
        return $this->value;
    }
}
