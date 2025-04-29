<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;

use Wideti\DomainBundle\Entity\AccessPointExtraConfig;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Gateways\Ruckus\PostRuckusCloudGateway;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class RuckuscloudHandler implements NasParameterHandler, NasExtraConfig
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

    private $secretKey;

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
        $guestMac = $this->decryptUserMac($this->requestParameters[$this->fields->getNasUrlPostField()],$guestMac);
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
            'https',
            $urlValue,
            '9997',
            '/login'
        );

        if (array_key_exists('uamip', $this->requestParameters)) {
            $nasPostUrl = new NasFormPostParameter(
                'https',
                $urlValue,
                '3990',
                '/login'
            );
        }

        if (array_key_exists('nbiIP', $this->requestParameters)) {
            $nasPostUrl = new NasFormPostParameter(
                'https',
                $urlValue,
                '443',
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
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = isset($params['StartURL']) ? $params['StartURL'] : "";
        $extraParams[Nas::EXTRA_PARAM_PROXY]        = isset($params['proxy']) ? $params['proxy'] : "";
        $extraParams[Nas::EXTRA_PARAM_USER_IP]      = isset($params['uip']) ? $params['uip'] : "";
        return $extraParams;
    }

    /**
     * @param PostRuckusCloudGateway $ruckusCloudGateway
     * @param string $encodedMac
     * @return string
     */
    private function decryptUserMac($ruckusPostUrl, $encodedMac) {

        $encodedMacBody = [
            "Vendor" => "Ruckus",
            "APIVersion" => "1.0",
            "RequestUserName" => "api",
            "RequestPassword" => $this->secretKey,
            "RequestCategory" => "GetConfig",
            "RequestType" => "DecryptIP",
            "UE-IP" => $encodedMac
        ];

        $decodedMac = $encodedMac;

        try {
            $response = PostRuckusCloudGateway::post("https://".$ruckusPostUrl, $encodedMacBody);
            $decodedMac = $response->getDecUeIp();
        }catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $this->formatMac($decodedMac);
    }



    /**
     * @param AccessPointExtraConfig $extraConfig
     * @return void
     */
    public function setExtraConfig($extraConfig)
    {
        $this->secretKey = $extraConfig->getValue();
    }

    /**
     * @param string $mac
     * @return string
     */
    private function formatMac($mac) {
        $mac = strtoupper($mac);
        $pattern = '%%-%%-%%-%%-%%-%%';
        return vsprintf(str_replace('%', '%s', $pattern), str_split($mac));
    }
}
