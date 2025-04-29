<?php

namespace Wideti\DomainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EmailValidateEvent extends Event
{
    protected $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }
}
