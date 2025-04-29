<?php


namespace Wideti\DomainBundle\Analytics\Events;


interface AnalyticEventLogger
{
    function formatEvent($event);

}
