<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class EventSignIn implements EventType
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
        return new EventSignIn("sign_in");
    }

    public function getValue()
    {
        return $this->value;
    }
}