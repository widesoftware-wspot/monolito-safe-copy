<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventMove implements EventType
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
        return new EventMove("move");
    }

    public function getValue()
    {
        return $this->value;
    }
}