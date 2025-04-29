<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\GrandstreamFields;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class GrandstreamHandler implements NasParameterHandler
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
        $controllerUrl = $this->requestParameters[$this->fields->getNasUrlPostField()];
        $host = StringHelper::getHost($controllerUrl);
        return new NasFormPostParameter(
            'https',
            $host,
            '8443',
            '/gwn_login'
        );
    }

    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL]                 = isset($params['orig_url']) ? $params['orig_url'] : "";
        $extraParams[Nas::EXTRA_PARAM_SSID]                         = isset($params['ssid']) ? $params['ssid'] : "";
        $extraParams[GrandstreamFields::EXTRA_PARAM_CLIENT_MAC]     = isset($params['client_mac']) ? $params['client_mac'] : "";
        return $extraParams;
    }
}