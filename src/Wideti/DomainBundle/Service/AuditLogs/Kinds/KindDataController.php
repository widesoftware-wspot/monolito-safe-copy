<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindDataController implements Kind
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
        return new KindDataController("data_controller");
    }

    public function getValue()
    {
        return $this->value;
    }
}
