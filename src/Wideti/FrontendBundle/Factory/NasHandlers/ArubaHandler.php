<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 26/07/17
 * Time: 17:22
 */

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class ArubaHandler implements NasParameterHandler
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
        $guestMac   = NasHelper::makeMac($this->requestParameters[$this->fields->getGuestMacField()]);
        $apMac      = NasHelper::makeIdentity($this->requestParameters[$this->fields->getApMacField()]);

        $nasBuilder = new NasBuilder();
        return $nasBuilder
                ->withGuestDeviceMacAddress($guestMac)
                ->withAccessPointMacAddress($apMac)
                ->withVendorName($this->vendorName)
                ->withVendorRawParameters($this->requestParameters)
                ->withExtraParams($this->getExtraParams())
                ->withNasUrlPost($this->getNasUrlPost())
                ->build();
    }

    /**
     * @return bool
     *
     * If have fields apmac or ap_mac is not a aruba controller
     */
    public function isArubaController()
    {
        if(array_key_exists('apmac', $this->requestParameters)
            || array_key_exists('ap_mac', $this->requestParameters)) {
            return false;
        }
        return true;
    }

    /**
     * @return NasFormPostParameter
     */
    public function getNasUrlPost()
    {
        return new NasFormPostParameter(
            'https',
            $this->requestParameters[$this->fields->getNasUrlPostField()],
            null,
            '/auth/index.html/u'
        );
    }

    /**
     * @return array
     */
    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $extraParam[Nas::EXTRA_PARAM_SSID]         = isset($params['essid']) ? $params['essid'] : "";
        $extraParam[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['url']) ? $params['url'] : "";

        return $extraParam;
    }
}
