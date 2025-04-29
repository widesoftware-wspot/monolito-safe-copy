<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class TplinkcloudFields implements RequiredFields
{
    const EXTRA_PARAM_RADIO_ID      = "radioId";
    const EXTRA_PARAM_CLIENT_IP     = "clientIp";
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
        return ['ap'];
    }
    public function getGuestMacFields()
    {
        return ['clientMac'];
    }
    public function getNasUrlPostFields()
    {
        return ['target'];
    }
}
