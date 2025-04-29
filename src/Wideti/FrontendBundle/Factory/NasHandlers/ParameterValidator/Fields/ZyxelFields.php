<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class ZyxelFields implements RequiredFields
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
        return ['uag_mac'];
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
        return ['gw_addr'];
    }
}
