<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\UnifinewFields;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class UnifiHandler implements NasParameterHandler, NasExtraConfig
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

    private $controllerUrl;

    /**
     * @param array $requestParameters
     * @param string $vendorName
     * @param ParameterValidator $validator
     * @throws NasWrongParametersException
     */
    public function __construct(array $requestParameters, $vendorName, ParameterValidator $validator)
    {
        $this->requestParameters = $requestParameters;
        $this->vendorName = strtolower($vendorName);
        $this->fields = $validator->validate();
    }

    /**
     * @return Nas
     */
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

    /**
     * @return NasFormPostParameter
     */
    public function getNasUrlPost()
    {
        return new NasFormPostParameter(
            'https',
            'unifi-aaa.mambowifi.com',
            null,
            '/start-navigation'
        );
    }

    /**
     * @return array
     */
    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[UnifinewFields::SITE_KEY] = $this->getSite($params);


        if ($this->controllerUrl) {
            $host = StringHelper::getHost($this->controllerUrl);
            $port = StringHelper::getPort($this->controllerUrl);
            $login_url = $this->replaceDomainLoginUrl($params, $host, $port);
        } else {
            $login_url = isset($params['login_url']) ? $params['login_url'] : "";
        }
        $login_url = str_replace("/index.html", "", $login_url);
        $extraParams[UnifinewFields::LOGIN_URL_KEY] = $login_url;

        $extraParams[Nas::EXTRA_PARAM_SSID] = isset($params['ssid']) ? $params['ssid'] : "";
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['url']) ? $params['url'] : "";

        $domain = $params["client_domain"];
        if (!strpos($domain, '.'))
            $domain .= ".mambowifi.com";
        $domain .= "/authorize-error-url";
        $extraParams[Nas::EXTRA_PARAM_AUTHORIZE_ERROR_URL] = $domain;

        return $extraParams;
    }

    protected function getSite($params)
    {
        if (!isset($params['login_url'])) {
            return '';
        }
        $arrLoginUrl = explode('/s/', $params['login_url']);
        if (!isset($arrLoginUrl[1])) {
            return '';
        }
        $arrPath = explode('/', $arrLoginUrl[1]);
        if (!isset($arrPath[0])) {
            return '';
        }
        return $arrPath[0];
    }

    protected function replaceDomainLoginUrl($params, $host, $port)
    {
        if (!isset($params['login_url'])) {
            return '';
        }
        $arrLoginUrl = explode('/s/', $params['login_url']);

        if ($port) {
            return "https://".$host.":".$port."/guest/s/".$arrLoginUrl[1];
        }
        return "https://".$host."/guest/s/".$arrLoginUrl[1];
    }

    public function setExtraConfig($extraConfig) {
        $this->controllerUrl = $extraConfig->getValue();
    }
}
