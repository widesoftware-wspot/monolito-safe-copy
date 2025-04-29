<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindSegmentation implements Kind
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
        return new KindSegmentation("segmentation");
    }

    public function getValue()
    {
        return $this->value;
    }
}
