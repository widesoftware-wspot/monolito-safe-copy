<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventAccept implements EventType
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
        return new EventAccept("accept");
    }

    public function getValue()
    {
        return $this->value;
    }
}