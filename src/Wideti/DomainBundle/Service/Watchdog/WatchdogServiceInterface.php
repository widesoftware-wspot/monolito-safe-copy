<?php

namespace Wideti\DomainBundle\Service\Watchdog;

interface WatchdogServiceInterface
{
    public function execute();
    public function send($params = []);
}
