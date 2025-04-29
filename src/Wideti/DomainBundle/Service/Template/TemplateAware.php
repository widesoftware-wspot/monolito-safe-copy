<?php

namespace Wideti\DomainBundle\Service\Template;

use Wideti\DomainBundle\Service\Template\TemplateService;

/**
 *
 * Usage: - [ setTemplateService, ["@core.service.template"] ]
 */
trait TemplateAware
{
    /**
     * @var templateService
     */
    protected $templateService;

    public function setTemplateService(TemplateService $service)
    {
        $this->templateService = $service;
    }
}
