<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventImport implements EventType
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
        return new EventImport("import");
    }

    public function getValue()
    {
        return $this->value;
    }
}