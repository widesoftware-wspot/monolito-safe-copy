<?php

namespace Wideti\DomainBundle\Service\Radacct;

/**
 *
 * Usage: - [ setRadacctService, [@core.service.radacct] ]
 */
trait RadacctServiceAware
{
    /**
     * @var RadacctService
     */
    protected $radacctService;

    public function setRadacctService(RadacctService $radacctService)
    {
        $this->radacctService = $radacctService;
    }
}
