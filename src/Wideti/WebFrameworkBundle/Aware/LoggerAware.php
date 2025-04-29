<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Bridge\Monolog\Logger;

/**
 * Symfony Server Setup: - [ setLogger, ["@logger"] ]
 */
trait LoggerAware
{
    /**
     * @var Logger
     */
    protected $logger;

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}
