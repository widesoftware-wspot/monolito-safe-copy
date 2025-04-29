<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\Tplinkv4cloudFields;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class Tplinkv4cloudHandler implements NasParameterHandler, NasExtraConfig
{
    private $requestParameters;
    private $vendorName;
    private $fields;
    private $controllerUrl;
    public function __construct(array $requestParameters, $vendorName, ParameterValidator $validator)
    {
        $this->requestParameters = $requestParameters;
        $this->vendorName = strtolower($vendorName);
        $this->fields = $validator->validate();
    }

    public function buildNas()
    {
        $guestMac = NasHelper::makeMac($this->requestParameters[$this->fields->getGuestMacField()]);
        $apMac = NasHelper::makeIdentity($this->requestParameters[$this->fields->getApMacField()]);

        $builder = new NasBuilder();
        return $builder->withAccessPointMacAddress($apMac)
            ->withGuestDeviceMacAddress($guestMac)
            ->withVendorName($this->vendorName)
            ->withExtraParams($this->getExtraParams())
            ->withVendorRawParameters($this->requestParameters)
            ->withNasUrlPost($this->getNasUrlPost())
            ->build();
    }

    public function getNasUrlPost()
    {
        $protocol = StringHelper::getProtocol($this->controllerUrl);
        $host = StringHelper::getHost($this->controllerUrl);
        $port = StringHelper::getPort($this->controllerUrl);
        return new NasFormPostParameter(
            $protocol,
            $host,
            $port,
            '/portal/radius/auth'
        );
    }

    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams['scheme'] = isset($params['scheme']) ? $params['scheme'] : "";
        $extraParams[Nas::EXTRA_PARAM_SSID] = isset($params['ssidName']) ? $params['ssidName'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_RADIO_ID] = isset($params['radioId']) ? $params['radioId'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_RADIUS_SERVER_IP] = isset($params['radiusServerIp']) ? $params['radiusServerIp'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_GATEWAY_MAC] = isset($params['gatewayMac']) ? $params['gatewayMac'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_CLIENT_IP] = isset($params['clientIp']) ? $params['clientIp'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_VID] = isset($params['vid']) ? $params['vid'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_TARGET_PORT] = isset($params['targetPort']) ? $params['targetPort'] : "";
        $extraParams[Tplinkv4cloudFields::EXTRA_PARAM_TARGET] = isset($params['target']) ? $params['target'] : "";
        return $extraParams;
    }

    public function setExtraConfig($extraConfig) {
        $this->controllerUrl = $extraConfig->getValue();
    }
}