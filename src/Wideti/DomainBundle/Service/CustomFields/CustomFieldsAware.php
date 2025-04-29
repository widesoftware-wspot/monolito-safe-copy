<?php

namespace Wideti\DomainBundle\Service\CustomFields;

/**
 * Symfony Server Setup: - [ setCustomFieldsService, ["@core.service.custom_fields"] ]
 */
trait CustomFieldsAware
{
    /**
     * @var CustomFieldsService
     */
    public $customFieldsService;

    public function setCustomFieldsService(CustomFieldsService $service)
    {
        $this->customFieldsService = $service;
    }
}
