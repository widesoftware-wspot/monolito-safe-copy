<?php


namespace Wideti\DomainBundle\Analytics;

use Fluent\Logger\FluentLogger;
use Wideti\DomainBundle\Analytics\Events\AnalyticEventLogger;
use Wideti\DomainBundle\Analytics\Events\Event;

abstract class EventLoggerManager
{
    /**
     * @var FluentLogger
     */
    private $logger;

    private $analyticApiKey;


    public function __construct($host, $port, $analyticApiKey)
    {
        $this->logger = new FluentLogger($host, $port);
        $this->analyticApiKey = $analyticApiKey;
    }


    /**
     * @param Event $logEvent
     */
    public function sendLog($logEvent)
    {
        $analyticEventLogger = $this->createAnalyticLogger();

        $domain = $logEvent->getClient() ? $logEvent->getClient()->getDomain() : "N/I";

        if ($domain == 'thiago') {
            return;
        }

        $event = $analyticEventLogger->formatEvent($logEvent);
        $this->logger->post("wspot.analytics.events",
            [ "api_key"=> $this->analyticApiKey ,
                "events" =>
                    [
                        $event
                    ]

            ]
        );
    }

    /**
     * @return AnalyticEventLogger
     */
    abstract public function createAnalyticLogger();


}
