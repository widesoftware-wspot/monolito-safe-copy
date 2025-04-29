<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventExport implements EventType
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
        return new EventExport("export");
    }

    public function getValue()
    {
        return $this->value;
    }
}