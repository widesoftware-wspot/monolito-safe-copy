<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

interface NasParameterHandler
{
    /**
     * @param array $requestParameters
     * @param string $vendorName
     * @param ParameterValidator $validator
     * @throws NasWrongParametersException
     */
    public function __construct(array $requestParameters, $vendorName, ParameterValidator $validator);

    /**
     * @return Nas
     */
    public function buildNas();

    /**
     * @return NasFormPostParameter
     */
    public function getNasUrlPost();

    /**
     * @return array
     */
    public function getExtraParams();

}