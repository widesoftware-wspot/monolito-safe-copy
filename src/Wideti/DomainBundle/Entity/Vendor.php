<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\VendorRepository")
 */
class Vendor
{
    const AEROHIVE                          = 'aerohive';
    const ARUBA                             = 'aruba';
    const ARUBA_V2                          = 'aruba_v2';
    const CISCO                             = 'cisco';
    const MIKROTIK                          = 'mikrotik';
    const PFSENSE                           = 'pfsense';
    const RUCKUS                            = 'ruckus-controlador';
    const RUCKUS_STANDALONE                 = 'ruckus-standalone';
    const RUCKUS_CLOUD                      = 'ruckus-cloud';
    const ZYXEL                             = 'zyxel';
    const FORTINET                          = 'fortinet';
    const CAMBIUM                           = 'cambium';
    const WATCHGUARD                        = 'watchguard';
    const CISCO_MERAKI_CLOUD                = 'cisco-meraki-cloud';
    const UNIFI_UBIQUITI                    = 'unifi-ubiquiti';
    const DEFAULT_ROUTERMODE                = 'router';
    const TP_LINK                           = 'tp-link';
    const TP_LINK_CLOUD                     = 'tp-link-cloud';
    const TP_LINK_V4                        = 'tp-link-v4';
    const TP_LINK_V4_CLOUD                  = 'tp-link-v4-cloud';
    const TP_LINK_V5                        = 'tp-link-v5';
    const TP_LINK_V5_CLOUD                  = 'tp-link-v5-cloud';
    const ENTERASYS                         =  'enterasys';
    const MOTOROLA                          =  'motorola';
    const WINCO                             =  'winco';
    const XIRRUS                            =  'xirrus';
    const INTELBRAS                         =  'intelbras';
    const INTELBRAS_FUTURE                  =  'intelbras_future';
    const TELTONICA                         =  'teltonika';
    const EDGECORE                          =  'edgecore';
    const HUAWEI                            =  'huawei';
    const Xirrus                            =  'xirrus';
    const GRANDSTREAM                       =  'grandstream';
    const DRAYTEK                           =  'draytek';
    const OPENWIFI                          =  'openwifi';
    const RUIJIE                            =  'ruijie';
    const EXTREME_CLOUD_XIQ_CONTROLLER      =  'extreme-networks';

    const VENDOR_MAP = [
        vendor::AEROHIVE            =>  'Extreme - Aerohive - ExtremeCloud IQ',
        vendor::ARUBA               =>  'Aruba',
        vendor::ARUBA_V2            =>  'Aruba v2',
        vendor::CISCO               =>  'Cisco',
        vendor::MIKROTIK            =>  'Mikrotik',
        vendor::PFSENSE             =>  'PfSense',
        vendor::RUCKUS_STANDALONE   =>  'Ruckus-Standalone',
        vendor::RUCKUS              =>  'Ruckus-Controlador',
        vendor::RUCKUS_CLOUD        =>  'Ruckus-Cloud',
        vendor::ZYXEL               =>  'ZyXEL',
        vendor::CAMBIUM             =>  'Cambium',
        vendor::WATCHGUARD          =>  'WatchGuard',
        vendor::CISCO_MERAKI_CLOUD  =>  'Cisco Meraki Cloud',
        vendor::UNIFI_UBIQUITI      =>  'Unifi Ubiquiti',
//        vendor::DEFAULT_ROUTERMODE  =>  '',
        vendor::TP_LINK             =>  'Tp-Link',
        vendor::TP_LINK_CLOUD       =>  'Tp-Link Cloud',
        vendor::TP_LINK_V4          =>  'Tp-Link v4',
        vendor::TP_LINK_V4_CLOUD    =>  'Tp-Link v4 Cloud',
        vendor::TP_LINK_V5          =>  'Tp-Link v5',
        vendor::TP_LINK_V5_CLOUD    =>  'Tp-Link v5 Cloud',
        vendor::FORTINET            =>  'Fortinet',
        vendor::ENTERASYS           =>  'ExtremeCloud IQ Controller - Enterasys - IdentiFi',
        vendor::MOTOROLA            =>  'Extreme - Motorola - WiNG',
        vendor::WINCO               =>  'Winco',
        vendor::XIRRUS              =>  'Xirrus',
        vendor::INTELBRAS           =>  'Intelbras',
        vendor::INTELBRAS_FUTURE    =>  'Intelbras - Linha Future',
        vendor::TELTONICA           =>  'Teltonika',
        vendor::EDGECORE            =>  'Edgecore',
        vendor::HUAWEI              =>  'Huawei',
        vendor::Xirrus              =>  'Xirrus',
        vendor::GRANDSTREAM         =>  'Grandstream',
        vendor::DRAYTEK             =>  'Draytek',
        vendor::OPENWIFI            =>  'Openwifi',
        vendor::RUIJIE              =>  'Ruijie Networks',
        Vendor::EXTREME_CLOUD_XIQ_CONTROLLER =>  'ExtremeCloud XIQ Controller'
    ];

    const VENDOR_MAP_BY_DISPLAY_NAME = [
        'Extreme - Aerohive - ExtremeCloud IQ' => vendor::AEROHIVE,
        'Aruba'                                => vendor::ARUBA,
        'Aruba v2'                             => vendor::ARUBA_V2,
        'Cisco'                                => vendor::CISCO,
        'Mikrotik'                             => vendor::MIKROTIK,
        'PfSense'                              => vendor::PFSENSE,
        'Ruckus-Standalone'                    => vendor::RUCKUS_STANDALONE,
        'Ruckus-Controlador'                   => vendor::RUCKUS,
        'Ruckus-Cloud'                         => vendor::RUCKUS_CLOUD,
        'ZyXEL'                                => vendor::ZYXEL,
        'Cambium'                              => vendor::CAMBIUM,
        'Cisco Meraki Cloud'                   => vendor::CISCO_MERAKI_CLOUD,
        'Unifi Ubiquiti'                       => vendor::UNIFI_UBIQUITI,
        'Tp-Link'                              => vendor::TP_LINK,
        'Tp-Link Cloud'                        => vendor::TP_LINK_CLOUD,
        'Tp-Link v4'                           => vendor::TP_LINK_V4,
        'Tp-Link v4 Cloud'                     => vendor::TP_LINK_V4_CLOUD,
        'Tp-Link v5'                           => vendor::TP_LINK_V5,
        'Tp-Link v5 Cloud'                     => vendor::TP_LINK_V5_CLOUD,
        'Fortinet'                             => vendor::FORTINET,
        'ExtremeCloud IQ Controller - Enterasys - IdentiFi'       => vendor::ENTERASYS,
        'Extreme - Motorola - WiNG'            => vendor::MOTOROLA,
        'Winco'                                => vendor::WINCO,
        'Xirrus'                               => vendor::XIRRUS,
        'Intelbras'                            => vendor::INTELBRAS,
        'Intelbras - Linha Future'             => vendor::INTELBRAS_FUTURE,
        'Teltonika'                            => vendor::TELTONICA,
        'Edgecore'                             => vendor::EDGECORE,
        'Huawei'                               => vendor::HUAWEI,
        'Grandstream'                          => vendor::GRANDSTREAM,
        'Draytek'                              => vendor::DRAYTEK,
        'Openwifi'                             => vendor::OPENWIFI,
        'WatchGuard'                           => vendor::WATCHGUARD,
        'Ruijie Networks'                      => vendor::RUIJIE,
        'ExtremeCloud XIQ Controller'          => vendor::EXTREME_CLOUD_XIQ_CONTROLLER
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="vendor", type="string", length=50, nullable=false)
     */
    private $vendor;
    /**
     * @ORM\Column(name="manual", type="text")
     */
    private $manual;
    /**
     * @ORM\Column(name="mask", type="string", length=50, nullable=true)
     */
    private $mask;
    /**
     * @ORM\Column(name="router_mode", type="string", length=20)
     */
    private $routerMode;

    /**
     * @ORM\Column(name="is_homologated", type="boolean", options={"default":false})
     */
    private $isHomologated;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return string
     */
    public function getManual()
    {
        return $this->manual;
    }

    /**
     * @param string $manual
     */
    public function setManual($manual)
    {
        $this->manual = $manual;
    }

    /**
     * @return string
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @param string $mask
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
    }

    /**
     * @return mixed
     */
    public function getRouterMode()
    {
        return $this->routerMode;
    }

    /**
     * @param mixed $routerMode
     */
    public function setRouterMode($routerMode)
    {
        $this->routerMode = $routerMode;
    }

    /**
     * @return mixed
     */
    public function getIsHomologated()
    {
        return $this->isHomologated;
    }

    /**
     * @param mixed $isHomologated
     */
    public function setIsHomologated($isHomologated)
    {
        $this->isHomologated = $isHomologated;
    }
}

