<?php


namespace Wideti\DomainBundle\Service\AuditLogs;


use Fluent\Logger\FluentLogger;

class FluentdAuditor implements Auditor
{

    /**
     * @var FluentLogger
     */
    private $logger;
    /**
     * @var $serviceName string
     */
    private $serviceName;
    /**
     * @var $tag string
     */
    private $tag;

    /**
     * AuditLogsServiceImp constructor.
     * @param $fluentdPort string
     * @param $fluentdAddress string
     * @param $serviceName string
     * @param $tag string
     */
    public function __construct($fluentdPort, $fluentdAddress, $serviceName, $tag)
    {
        $this->logger = $this->logger = new FluentLogger($fluentdAddress, $fluentdPort);
        $this->serviceName = $serviceName;
        $this->tag = $tag;
    }

    /**
     * @param AuditEvent $event
     * @throws AuditException
     */
    public function push(AuditEvent $event)
    {
        $message = $event->asMap();
        $this->logger->post($this->tag, $message);
    }

    /**
     * @return AuditEvent
     */
    public function newEvent()
    {
        return AuditEvent::start($this->serviceName);
    }
}