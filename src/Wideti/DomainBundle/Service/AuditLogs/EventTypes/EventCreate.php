<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventCreate implements EventType
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
        return new EventCreate("create");
    }

    public function getValue()
    {
        return $this->value;
    }
}