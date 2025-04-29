<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class RuckuscloudFields implements RequiredFields
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
        return ['mac'];
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
        $field = ['nbiIP'];
        if (array_key_exists('uamip', $this->rawParameters)) {
            $field = ['uamip'];
        }
        return $field;
    }
}