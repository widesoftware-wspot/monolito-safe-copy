<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class DraytekHandler implements NasParameterHandler
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
        $controllerUrl = $this->requestParameters[$this->fields->getNasUrlPostField()];
        $host = StringHelper::getHost($controllerUrl);
        return new NasFormPostParameter(
            'https',
            $host,
            '8043',
            '/cgi-bin/wifilogin.cgi'
        );
    }

    /**
     * @return array
     */
    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['target']) ? $params['target'] : "";
        return $extraParams;
    }
}
