<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\AuthReport;


use Wideti\DomainBundle\Helpers\DeviceHelper;
use Wideti\DomainBundle\Service\GuestAccessData\GuestAccessDataHelper;
use Wideti\FrontendBundle\Factory\Nas;

class AccessDataEvent implements \JsonSerializable
{
    private $os;
    private $platform;
    private $macAddress;

    private function __construct() {}

    /**
     * @param Nas|null $nas
     * @return AccessDataEvent
     */
    public static function createFrom(Nas $nas = null)
    {
        $report = new AccessDataEvent();

        if (!$nas) {
            return $report;
        }

        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $accessData = DeviceHelper::getAccessDataInfo($u_agent);

        $report->setMacAddress($nas->getGuestDeviceMacAddress());
        $report->setOs($accessData['os']);
        $report->setPlatform($accessData['device']);

        return $report;
    }

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param mixed $os
     */
    public function setOs($os)
    {
        $this->os = $os;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param mixed $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return mixed
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * @param mixed $macAddress
     */
    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'os' => $this->os,
            'platform' => $this->platform,
            'macAddress' => $this->macAddress
        ];
    }
}
