<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventView implements EventType
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
        return new EventView("view");
    }

    public function getValue()
    {
        return $this->value;
    }
}