<?php

namespace Wideti\DomainBundle\Service\Queue;

use Wideti\DomainBundle\Service\Queue\QueueService;

/**
 *
 * Usage: - [ setQueue, ["@core.queue_service"] ]
 */
trait QueueServiceAware
{
    /**
     * @var QueueService
     */
    protected $queue;

    public function setQueue(QueueService $service)
    {
        $this->queue = $service;
    }
}
