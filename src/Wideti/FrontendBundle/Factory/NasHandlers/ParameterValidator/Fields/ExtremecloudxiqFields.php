<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class ExtremecloudxiqFields implements RequiredFields
{
    /**
     * @var array
     */
    private $rawParameters;

    /**
     * RequiredFields constructor.
     * @param array $rawParameters
     */
    public function __construct(array $rawParameters)
    {
        $this->rawParameters = $rawParameters;
    }

    /**
     * @return array
     */
    public function getApMacFields()
    {
        return ['sn'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        return ['mac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['hwc_ip'];
    }
}
