<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindWhiteLabel implements Kind
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
        return new KindWhiteLabel("white_label");
    }

    public function getValue()
    {
        return $this->value;
    }
}
