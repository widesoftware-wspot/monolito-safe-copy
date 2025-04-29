<?php

namespace Wideti\DomainBundle\Service\Fluentd;

use Fluent\Logger\FluentLogger;

class FluentdServiceImp implements FluentdService
{
    /**
     * @var FluentLogger
     */
    private $logger;

    /**
     * AuditLogsServiceImp constructor.
     * @param $host
     * @param $port
     */
    public function __construct($host, $port)
    {
        $this->logger = new FluentLogger($host, $port);
    }

    public function send($tag, $data)
    {
        $this->logger->post($tag, $data);
    }
}
