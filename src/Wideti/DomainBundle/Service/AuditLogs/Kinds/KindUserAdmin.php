<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class KindUserAdmin implements Kind
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
        return new KindUserAdmin("user_admin");
    }

    public function getValue()
    {
        return $this->value;
    }
}
