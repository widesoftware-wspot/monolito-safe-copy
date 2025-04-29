<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class IntelbrasFields implements RequiredFields
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
        return ['mac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['redirect_uri'];
    }
}
