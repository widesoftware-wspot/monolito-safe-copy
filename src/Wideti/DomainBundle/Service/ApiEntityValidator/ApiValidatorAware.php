<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator;

/**
 * Para usar, injete a implementação desejada, exemplo esta injetando o validador de Guest
 *  - [ setApiValidator, ["@core.service.guest_api_validator"] ]
 *  - [ setApiValidator, ["@core.service.segmentation_api_validator"] ]
 */
trait ApiValidatorAware
{
    /**
     * @var ApiValidator
     */
    protected $apiValidator;

    public function setApiValidator(ApiValidator $validator)
    {
        $this->apiValidator = $validator;
    }
}