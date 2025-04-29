<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class CambiumFields implements RequiredFields
{
    /**
     * @var array $rawParameters
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
        return ['ga_ap_mac'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        return ['ga_cmac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['ga_srvr'];
    }
}