<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\Tplinkv5Fields;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class Tplinkv5Handler implements NasParameterHandler
{
    private $requestParameters;
    private $vendorName;
    private $fields;
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
        return new NasFormPostParameter(
            'http',
            $this->requestParameters[$this->fields->getNasUrlPostField()],
            '8088',
            '/portal/radius/browserauth'
        );
    }

    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[Nas::EXTRA_PARAM_SSID] = isset($params['ssidName']) ? $params['ssidName'] : "";
        $extraParams[Tplinkv5Fields::EXTRA_PARAM_RADIO_ID] = isset($params['radioId']) ? $params['radioId'] : "";
        $extraParams[Tplinkv5Fields::EXTRA_PARAM_CLIENT_IP] = isset($params['clientIp']) ? $params['clientIp'] : "";
        $extraParams[Tplinkv5Fields::EXTRA_PARAM_TARGET_PORT] = isset($params['targetPort']) ? $params['targetPort'] : "";
        return $extraParams;
    }
}