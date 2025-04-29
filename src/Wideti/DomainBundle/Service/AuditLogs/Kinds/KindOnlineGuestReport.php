<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindOnlineGuestReport implements Kind
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
        return new KindOnlineGuestReport("online_guest_report");
    }

    public function getValue()
    {
        return $this->value;
    }
}
