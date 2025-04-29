<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class DraytekFields implements RequiredFields
{
    /**
     * @var array
     */
    private $rawParameters;
    public function __construct(array $rawParameters)
    {
        $this->rawParameters = $rawParameters;
    }
    public function getApMacFields()
    {
        return ['apmac'];
    }
    public function getGuestMacFields()
    {
        return ['client_mac'];
    }
    public function getNasUrlPostFields()
    {
        return ['loginurl'];
    }
}
