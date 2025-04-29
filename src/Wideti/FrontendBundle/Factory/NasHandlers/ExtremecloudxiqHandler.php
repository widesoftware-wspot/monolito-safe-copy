<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Stringable;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class ExtremecloudxiqHandler implements NasParameterHandler
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
        $host = StringHelper::getHost($this->requestParameters[$this->fields->getNasUrlPostField()]);
        return new NasFormPostParameter(
            'https',
            $host,
            '443',
            '/ext_approval.php'
        );
    }

    /**
     * @return array
     */
    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParams[Nas::EXTRA_PARAM_SSID] = isset($params['ssid']) ? $params['ssid'] : "";
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['dest']) ? $params['dest'] : "";
        $extraParams[Nas::EXTRA_PARAM_TOKEN] = isset($params['token']) ? $params['token'] : "";
        return $extraParams;
    }
}
