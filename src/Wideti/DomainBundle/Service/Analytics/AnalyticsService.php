<?php

namespace Wideti\DomainBundle\Service\Analytics;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\Analytics\Dto\EventDto;

interface AnalyticsService
{
    public function handler(Request $request, $extra);
    public function sendEvent(EventDto $event);
}
