<?php


namespace Wideti\FrontendBundle\Factory\NasHandlers;


use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidator;

class EdgecoreHandler implements NasParameterHandler
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
            $this->requestParameters['uamport'],
            '/logon'
        );
    }

    public function getExtraParams()
    {
        return [];
    }
}
