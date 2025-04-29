<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class Tplinkv4Fields implements RequiredFields
{
    const EXTRA_PARAM_RADIO_ID         = "radioId";
    const EXTRA_PARAM_RADIUS_SERVER_IP = "radiusServerIp";
    const EXTRA_PARAM_CLIENT_IP        = "clientIp";
    const EXTRA_PARAM_SSID_NAME        = "ssidName";
    const EXTRA_PARAM_VID              = "vid";
    const EXTRA_PARAM_GATEWAY_MAC      = "gatewayMac";
    const EXTRA_PARAM_TARGET_PORT      = "targetPort";
    const EXTRA_PARAM_TARGET           = "target";
    const EXTRA_PARAM_SCHEME           = "scheme";

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
        return ['apMac'];
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