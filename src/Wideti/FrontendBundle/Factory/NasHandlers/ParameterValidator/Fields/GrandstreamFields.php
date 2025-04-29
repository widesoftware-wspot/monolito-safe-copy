<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class GrandstreamFields implements RequiredFields
{
    const EXTRA_PARAM_SSID     = "ssid";
    const EXTRA_PARAM_CLIENT_MAC     = "client_mac";
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
        return ['ap_mac'];
    }
    public function getGuestMacFields()
    {
        return ['client_mac'];
    }
    public function getNasUrlPostFields()
    {
        return ['login_url'];
    }
}
