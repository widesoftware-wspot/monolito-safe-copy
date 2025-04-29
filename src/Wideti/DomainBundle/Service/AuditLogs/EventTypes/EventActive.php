<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventActive implements EventType
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
        return new EventActive("active");
    }

    public function getValue()
    {
        return $this->value;
    }
}