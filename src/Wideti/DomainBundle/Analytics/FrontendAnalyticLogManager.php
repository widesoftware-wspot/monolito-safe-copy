<?php


namespace Wideti\DomainBundle\Analytics;


use Wideti\DomainBundle\Analytics\Events\AnalyticEventLogger;

class FrontendAnalyticLogManager extends EventLoggerManager
{

    public function createAnalyticLogger()
    {
        return new FrontendEventLogger();
    }
}
