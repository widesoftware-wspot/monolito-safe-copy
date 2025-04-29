<?php


namespace Wideti\FrontendBundle\Factory\NasHandlers;


use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class CiscocatalystHandler implements NasParameterHandler
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

    public function __construct(array $requestParameters, $vendorName, ParameterValidator $validator)
    {
        $this->requestParameters = $requestParameters;
        $this->vendorName = $vendorName;
        $this->fields = $validator->validate();
    }

    public function buildNas()
    {
        $guestMac = NasHelper::makeMac($this->requestParameters[$this->fields->getGuestMacField()]);
        $apMac = NasHelper::makeMac($this->requestParameters[$this->fields->getApMacField()]);

        $nasBuilder = new NasBuilder();
        return $nasBuilder
            ->withAccessPointMacAddress($apMac)
            ->withGuestDeviceMacAddress($guestMac)
            ->withVendorName($this->vendorName)
            ->withExtraParams($this->getExtraParams())
            ->withNasUrlPost($this->getNasUrlPost())
            ->withVendorRawParameters($this->requestParameters)
            ->build();
    }

    public function getNasUrlPost()
    {
        $nasUrl =
            str_replace("https", "http", $this->requestParameters[$this->fields->getNasUrlPostField()]);

        return new NasFormPostParameter(
            'http',
            $nasUrl,
            null,
            ''
        );
    }

    public function getExtraParams()
    {
        $params = $this->requestParameters;
        $url = isset($params['redirect']) ? $params['redirect'] : '';
        $extraParams[Nas::EXTRA_PARAM_REDIRECT_URL] = !empty($url) ? "https://{$url}" : "";
        return $extraParams;
    }
}