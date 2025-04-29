<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator;

/**
 * To use this: - [setCustomFieldValidator, [@core.api.validate.custom_field_validator]]
 */
trait CustomFieldValidatorAware
{
    /**
     * @var CustomFieldValidator
     */
    protected $customFieldValidator;

    public function setCustomFieldValidator(CustomFieldValidator $validator)
    {
        $this->customFieldValidator = $validator;
    }
}