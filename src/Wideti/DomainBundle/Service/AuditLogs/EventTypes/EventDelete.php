<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventDelete implements EventType
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
        return new EventDelete("delete");
    }

    public function getValue()
    {
        return $this->value;
    }
}