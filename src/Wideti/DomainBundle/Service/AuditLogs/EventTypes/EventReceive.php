<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventReceive implements EventType
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
        return new EventReceive("receive");
    }

    public function getValue()
    {
        return $this->value;
    }
}