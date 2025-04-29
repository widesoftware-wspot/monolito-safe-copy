<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers;


use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasImp;

class NasBuilder
{
    private $accessPointMacAddress;
    private $guestDeviceMacAddress;
    private $vendorName;
    private $extraParams;
    private $vendorRawParameters;
    private $nasFormPostParameter;

    /**
     * @param string $apMac
     * @return $this
     */
    public function withAccessPointMacAddress($apMac)
    {
        $this->accessPointMacAddress = $apMac;
        return $this;
    }

    /**
     * @param string $guestMac
     * @return $this
     */
    public function withGuestDeviceMacAddress($guestMac)
    {
        $this->guestDeviceMacAddress = $guestMac;
        return $this;
    }

    /**
     * @param string $vendorName
     * @return $this
     */
    public function withVendorName($vendorName)
    {
        $this->vendorName = $vendorName;
        return $this;
    }

    public function withExtraParams(array $extraParams)
    {
        $this->extraParams = $extraParams;
        return $this;
    }

    public function withVendorRawParameters(array $rawParameters)
    {
        $this->vendorRawParameters = $rawParameters;
        return $this;
    }

    public function withNasUrlPost(NasFormPostParameter $nasFormPostParameter)
    {
        $this->nasFormPostParameter = $nasFormPostParameter;
        return $this;
    }

    /**
     * @return Nas
     */
    public function build()
    {
        return new NasImp(
            $this->accessPointMacAddress,
            $this->guestDeviceMacAddress,
            $this->vendorName,
            $this->nasFormPostParameter,
            $this->extraParams,
            $this->vendorRawParameters
        );
    }
}
