<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class RuckusstandaloneHandler implements NasParameterHandler
{
    /**
     * @var array
     */
    private $requestParameters;
    /**
     * @var string
     */
    private $vendorName;
    /**
     * @var Dto\Fields
     */
    private $fields;

    /**
     * @param array $requestParameters
     * @param string $vendorName
     * @param ParameterValidator $validator
     * @throws NasWrongParametersException
     */
    public function __construct(array $requestParameters, $vendorName, ParameterValidator $validator)
    {
        $this->requestParameters = $requestParameters;
        $this->vendorName = $vendorName;
        $this->fields = $validator->validate();
    }

    /**
     * @return Nas
     */
    public function buildNas()
    {
        $guestMac = NasHelper::makeMac($this->requestParameters[$this->fields->getGuestMacField()]);
        $apMac = NasHelper::makeMac($this->requestParameters[$this->fields->getApMacField()]);

        $nasBuilder = new NasBuilder();
        return $nasBuilder
            ->withAccessPointMacAddress($apMac)
            ->withGuestDeviceMacAddress($guestMac)
            ->withVendorName($this->vendorName)
            ->withNasUrlPost($this->getNasUrlPost())
            ->withExtraParams($this->getExtraParams())
            ->withVendorRawParameters($this->requestParameters)
            ->build();
    }

    /**
     * @return NasFormPostParameter
     */
    public function getNasUrlPost()
    {
        $urlValue = $this->requestParameters[$this->fields->getNasUrlPostField()];

        $nasPostUrl = new NasFormPostParameter(
            'http',
            $urlValue,
            '9997',
            '/login'
        );

        if (array_key_exists('uamip', $this->requestParameters)) {
            $nasPostUrl = new NasFormPostParameter(
                'http',
                $urlValue,
                '3990',
                '/login'
            );
        }

        if (array_key_exists('nbiIP', $this->requestParameters)) {
            $nasPostUrl = new NasFormPostParameter(
                'http',
                $urlValue,
                '9997',
                '/SubscriberPortal/hotspotlogin'
            );
        }
        return $nasPostUrl;
    }

    /**
     * @return array
     */
    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[Nas::EXTRA_PARAM_SSID]         = isset($params['ssid']) ? $params['ssid'] : "";
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['url']) ? $params['url'] : "";
        $extraParams[Nas::EXTRA_PARAM_PROXY]        = isset($params['proxy']) ? $params['proxy'] : "";
        $extraParams[Nas::EXTRA_PARAM_USER_IP]      = isset($params['uip']) ? $params['uip'] : "";
        return $extraParams;
    }
}