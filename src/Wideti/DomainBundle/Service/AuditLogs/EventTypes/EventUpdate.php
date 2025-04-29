<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventUpdate implements EventType
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
        return new EventUpdate("update");
    }

    public function getValue()
    {
        return $this->value;
    }
}