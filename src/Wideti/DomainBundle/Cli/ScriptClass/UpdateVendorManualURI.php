<?php

namespace Wideti\DomainBundle\Cli\ScriptClass;

use Wideti\DomainBundle\Cli\AbstractScript;

/**
 * Class UpdateVendorManualURI
 * @package Wideti\DomainBundle\Cli
 */
class UpdateVendorManualURI extends AbstractScript
{
    const BASE_URL = "https://suporte.mambowifi.com/support/solutions/articles/";

    /**
     * @var array $updateValues
     */
    private $updateValues = [
        "Aerohive"           => "16000069502-configuracão-do-wspot-em-equipamentos-aerohive-hivemanager-",
        "Aruba"              => "16000089522-configuracão-do-wspot-em-equipamentos-aruba",
        "Cisco"              => "16000069464-configurac%C3%A3o-do-wspot-em-equipamentos-cisco-wlc-",
        "Enterasys"          => "16000089523-configuracão-do-wspot-em-equipamentos-enterasys",
        "Fortinet"           => "16000078401-configuracão-do-wspot-em-equipamentos-fortigate",
        "Mikrotik"           => "16000069505-configuracão-mikrotik-via-script",
        "Motorola"           => "16000077914-configuracão-do-wspot-na-controladora-motorola-rfs",
        "Winco"              => "16000089524-configuracão-do-wspot-em-equipamentos-winco",
        "Xirrus"             => "16000088085-configuracão-do-wspot-em-equipamentos-xirrus-ap-",
        "ZyXEL"              => "16000069491-configuracão-do-wspot-em-equipamentos-zyxel",
        "Ruckus-Controlador" => "16000069480-ruckus-controlador-configuracão-do-wspot",
        "Ruckus-Standalone"  => "16000069501-ruckus-modo-standalone-",
        "PfSense"            => "16000085077-configuracão-do-wspot-no-pfsense-versão-2-4-4"
    ];

    /**
     * UpdateVendorManualURI constructor.
     * @param $environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        return $this;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        foreach ($this->updateValues as $vendor => $resource) {
            $this->documentManager->getConnection()->getMongoClient()->wspot_system->vendors->update(
                ["vendor" => $vendor],
                ["\$set" => [ "manual" => self::BASE_URL . $resource ]]
            );
        }
    }
}