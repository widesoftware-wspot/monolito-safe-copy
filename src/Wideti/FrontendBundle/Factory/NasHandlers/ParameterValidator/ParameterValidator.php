<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator;


use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\Fields;

interface ParameterValidator
{
    /**
     * ParameterValidator constructor.
     * @param $vendorName
     * @param array $requestParameters
     */
    public function __construct($vendorName, array $requestParameters);

    /**
     * @return Fields
     * @throws NasWrongParametersException
     */
    public function validate();
}