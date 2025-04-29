<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventSignOut implements EventType
{
    private $value;

    private function __construct($value){
        $this->value = $value;
    }

    /**
     * @return EventType
     */
    public static function event()
    {
        return new EventSignOut("sign_out");
    }

    public function getValue()
    {
        return $this->value;
    }
}