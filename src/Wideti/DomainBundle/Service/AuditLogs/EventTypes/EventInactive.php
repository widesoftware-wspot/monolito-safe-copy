<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventInactive implements EventType
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
        return new EventInactive("inactive");
    }

    public function getValue()
    {
        return $this->value;
    }
}