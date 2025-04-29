<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class CiscoFields implements RequiredFields
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
        return ['ap_mac'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        return ['client_mac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['switch_url'];
    }
}
