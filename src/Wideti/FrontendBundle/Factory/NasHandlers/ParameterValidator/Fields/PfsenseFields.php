<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class PfsenseFields implements RequiredFields
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
        return ['portal_action'];
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
        return ['portal_action'];
    }
}
