<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindCampaign implements Kind
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
        return new KindCampaign("campaign");
    }

    public function getValue()
    {
        return $this->value;
    }
}
