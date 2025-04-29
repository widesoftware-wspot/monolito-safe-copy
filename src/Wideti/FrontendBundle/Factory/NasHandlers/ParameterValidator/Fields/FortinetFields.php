<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class FortinetFields implements RequiredFields
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
        return ['apmac'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        return ['usermac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['post'];
    }
}
