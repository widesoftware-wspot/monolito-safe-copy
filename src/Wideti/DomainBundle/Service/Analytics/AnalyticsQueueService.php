<?php

namespace Wideti\DomainBundle\Service\Analytics;

interface AnalyticsQueueService
{
	public function sendToQueue($message);
}