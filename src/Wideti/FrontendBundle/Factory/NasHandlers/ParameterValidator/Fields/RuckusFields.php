<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class RuckusFields implements RequiredFields
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
        if (array_key_exists('uamip', $this->rawParameters)) {
            return ['called'];
        }

        return ['mac'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        if (array_key_exists('uamip', $this->rawParameters)) {
            return ['mac'];
        }

        return ['client_mac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        $field = ['sip'];
        if (array_key_exists('uamip', $this->rawParameters)) {
            $field = ['uamip'];
        }
        return $field;
    }
}
