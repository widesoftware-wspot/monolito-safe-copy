<?php

namespace Wideti\DomainBundle\Service\NasCustomMiddleware;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\NasCustomMiddleware\NasMiddlewares\NasMiddleware;
use Wideti\FrontendBundle\Factory\Nas;

interface NasMiddlewareManager
{
    const VENDOR_RUCKUS            = "ruckus";
    const VENDOR_RUCKUS_STANDALONE = "ruckus_standalone";
    const VENDOR_MIKROTIK          = "mikrotik";
    const VENDOR_ARUBA             = "aruba";
    const VENDOR_ARUBA_V2      = "aruba_v2";
    const VENDOR_CISCO             = "cisco";
    const VENDOR_OPENWRT           = "openwrt";
    const VENDOR_AEROHIVE          = "aerohive";
    const VENDOR_COOVACHILLI       = "coovachilli";
    const VENDOR_INTELBRAS         = "intelbras";
    const VENDOR_FORTINET          = "fortinet";
    const VENDOR_PFSENSE           = "pfsense";
    const VENDOR_ENTERASYS         = "enterasys";
    const VENDOR_ZYXEL             = "zyxel";
    const VENDOR_CAMBIUM           = "cambium";

    /**
     * @param string $vendorName
     * @param Nas $nas
     * @param array $params
     * @param Client $client
     * @return Nas
     */
    public function handleNas($vendorName, Nas $nas = null, $params = [], Client $client);

    /**
     * @param NasMiddleware $nasMiddleware
     * @return void
     */
    public function registerMiddleware(NasMiddleware $nasMiddleware);
}