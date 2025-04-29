<?php

namespace Wideti\DomainBundle\Service\Dashboard;

use Wideti\DomainBundle\Service\Dashboard\DashboardService;

/**
 *
 * Usage: - [ setDashboardService, [@core.service.dashboard] ]
 */
trait DashboardAware
{
    /**
     * @var DashboardService
     */
    protected $dashboardService;

    public function setDashboardService(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
}