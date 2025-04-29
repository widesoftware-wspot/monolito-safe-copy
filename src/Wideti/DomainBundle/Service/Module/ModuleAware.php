<?php

namespace Wideti\DomainBundle\Service\Module;

use Wideti\DomainBundle\Service\Module\ModuleService;

/**
 *
 * Usage: - [ setModuleService, ["@core.service.module"] ]
 */
trait ModuleAware
{
    /**
     * @var moduleService
     */
    protected $moduleService;

    public function setModuleService(ModuleService $service)
    {
        $this->moduleService = $service;
    }
}
