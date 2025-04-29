<?php

namespace Wideti\DomainBundle\Helpers;
use Wideti\FrontendBundle\Form\Type\TelType;
use Wideti\FrontendBundle\Form\Type\FormCoovaChilliType;
use Wideti\FrontendBundle\Form\Type\FormMotorolaType;
use Wideti\FrontendBundle\Form\Type\FormDraytekType;
use Wideti\FrontendBundle\Form\Type\FormFortinetType;
use Wideti\FrontendBundle\Form\Type\FormCiscoMerakiCloudType;
use Wideti\FrontendBundle\Form\Type\FormRuckusstandaloneType;
use Wideti\FrontendBundle\Form\Type\FormRuijieType;
use Wideti\FrontendBundle\Form\Type\FormTplinkv5cloudType;
use Wideti\FrontendBundle\Form\Type\FormUnifiType;
use Wideti\FrontendBundle\Form\Type\FormWatchguardType;
use Wideti\FrontendBundle\Form\Type\FormFakeType;
use Wideti\FrontendBundle\Form\Type\FormIntelbrasType;
use Wideti\FrontendBundle\Form\Type\FormWincoType;
use Wideti\FrontendBundle\Form\Type\FormEdgecoreType;
use Wideti\FrontendBundle\Form\Type\FormRuckusCloudType;
use Wideti\FrontendBundle\Form\Type\FormTplinkv5Type;
use Wideti\FrontendBundle\Form\Type\FormExtremeCloudXiqType;
use Wideti\FrontendBundle\Form\Type\FormEnterasysType;
use Wideti\FrontendBundle\Form\Type\FormTeltonikaType;
use Wideti\FrontendBundle\Form\Type\FormCiscoCatalystType;
use Wideti\FrontendBundle\Form\Type\FormCambiumType;
use Wideti\FrontendBundle\Form\Type\FormArubaType;
use Wideti\FrontendBundle\Form\Type\FormAerohiveType;
use Wideti\FrontendBundle\Form\Type\FormPfSenseType;
use Wideti\FrontendBundle\Form\Type\FormOpenwifiType;
use Wideti\FrontendBundle\Form\Type\FormGrandstreamType;
use Wideti\FrontendBundle\Form\Type\FormPlenatechType;
use Wideti\FrontendBundle\Form\Type\FormXirrusType;
use Wideti\FrontendBundle\Form\Type\FormTplinkcloudType;
use Wideti\FrontendBundle\Form\Type\FormArubaV2Type;
use Wideti\FrontendBundle\Form\Type\FormHuaweiType;
use Wideti\FrontendBundle\Form\Type\FormUnifinewType;
use Wideti\FrontendBundle\Form\Type\FormIntelbrasFutureType;
use Wideti\FrontendBundle\Form\Type\FormCiscoType;
use Wideti\FrontendBundle\Form\Type\FormZyxelType;
use Wideti\FrontendBundle\Form\Type\FormTplinkv4Type;
use Wideti\FrontendBundle\Form\Type\FormRuckusType;
use Wideti\FrontendBundle\Form\Type\FormTplinkType;
use Wideti\FrontendBundle\Form\Type\FormMikrotikType;
use Wideti\FrontendBundle\Form\Type\FormTplinkv4cloudType;

final class NasHelper
{
    const MIN_MAC_LENGTH = 12;
    const MAX_MAC_LENGTH = 17;
    const formVendorsMap = [
        'tel' => TelType::class,
        'coovachilli' => FormCoovaChilliType::class,
        'motorola' => FormMotorolaType::class,
        'draytek' => FormDraytekType::class,
        'fortinet' => FormFortinetType::class,
        'cisco_meraki_cloud' => FormCiscoMerakiCloudType::class,
        'ruckus_standalone' => FormRuckusstandaloneType::class,
        'ruijie' => FormRuijieType::class,
        'tp_link_v5_cloud' => FormTplinkv5cloudType::class,
        'unifi' => FormUnifiType::class,
        'watchguard' => FormWatchguardType::class,
        'fake' => FormFakeType::class,
        'intelbras' => FormIntelbrasType::class,
        'winco' => FormWincoType::class,
        'edgecore' => FormEdgecoreType::class,
        'ruckus_cloud' => FormRuckusCloudType::class,
        'tp_link_v5' => FormTplinkv5Type::class,
        'extreme_cloud_xiq' => FormExtremeCloudXiqType::class,
        'enterasys' => FormEnterasysType::class,
        'teltonika' => FormTeltonikaType::class,
        'cisco_catalyst' => FormCiscoCatalystType::class,
        'cambium' => FormCambiumType::class,
        'aruba' => FormArubaType::class,
        'aerohive' => FormAerohiveType::class,
        'pfsense' => FormPfSenseType::class,
        'openwifi' => FormOpenwifiType::class,
        'grandstream' => FormGrandstreamType::class,
        'plenatech' => FormPlenatechType::class,
        'xirrus' => FormXirrusType::class,
        'tp_link_cloud' => FormTplinkcloudType::class,
        'aruba_v2' => FormArubaV2Type::class,
        'huawei' => FormHuaweiType::class,
        'unifinew' => FormUnifinewType::class,
        'intelbras_future' => FormIntelbrasFutureType::class,
        'cisco' => FormCiscoType::class,
        'zyxel' => FormZyxelType::class,
        'tp_link_v4' => FormTplinkv4Type::class,
        'ruckus' => FormRuckusType::class,
        'tp_link' => FormTplinkType::class,
        'mikrotik' => FormMikrotikType::class,
        'tp_link_v4_cloud' => FormTplinkv4cloudType::class,
    ];

    public function __construct()
    {
        throw new \Exception('Class is a Helper, can not be instantiate');
    }

    /**
     * @param array $rawParameters
     * @return string
     */
    static public function encodeRawParametersToUrl(array $rawParameters)
    {
        return urlencode(json_encode($rawParameters));
    }

    /**
     * @param string $serializedParameter
     * @return array
     */
    static public function decodeRawParametersToUrl($serializedParameter)
    {
        return json_decode(urldecode($serializedParameter), true);
    }

    /**
     * @param $identity
     * @return string
     */
    static public function makeIdentity($identity)
    {
        if (preg_match('/^([a-fA-F0-9]{2}\:){5}[a-fA-F0-9]{2}$/', $identity) === 1) {
            $identity = strtoupper(str_replace(':', '-', $identity));
        }

        if (preg_match('/^([a-fA-F0-9]{2}\-){5}[a-fA-F0-9]{2}$/', $identity) === 1) {
            $identity = strtoupper($identity);
        }

        return $identity;
    }

    /**
     * @param $mac
     * @return string
     */
    static public function makeMac($mac)
    {
        if (strlen($mac) < self::MIN_MAC_LENGTH) {
            return $mac;
        }

        $macReplaced = strtoupper(
            implode(
                '-',
                str_split(
                    preg_replace('/[^0-9A-Fa-f]/', '', $mac),
                    2
                )
            )
        );

        $macLength = strlen($macReplaced);
        return ($macLength === self::MAX_MAC_LENGTH ) ? $macReplaced : $mac;
    }

    /**
     * @param $mac
     * @param $nasParams
     * @return string
     */
    static public function makeMacByVendor($vendorName, $nasParams)
    {
        if ($vendorName == 'ruckus_cloud') {
            $mac = 'mac';
        } elseif (in_array($vendorName, ['unifi', 'unifinew'])) {
            $mac = 'ap_mac';
        } elseif ($vendorName == 'tp_link_cloud') {
            $mac = 'ap';
        } elseif (in_array($vendorName, ['tp_link_cloud', 'tp_link_v4_cloud', 'tp_link_v5_cloud'])) {
            $mac = 'apMac';
        }
        return NasHelper::makeMac($nasParams[$mac]);
    }

    /**
     * @param string $pfsenseZoneUrl
     * @return string
     */
    public static function makePfsenseIp($pfsenseZoneUrl)
    {
        if (empty($pfsenseZoneUrl)) {
            return '';
        }

        $zone = explode('?', $pfsenseZoneUrl)[1];

        if (preg_match('/^zone=ip-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}/', $zone)) {
            $noParsedIp = explode('=', $zone)[1];
            $ipNumbers = explode('-', $noParsedIp);
            return "{$ipNumbers[1]}.{$ipNumbers[2]}.{$ipNumbers[3]}.{$ipNumbers[4]}";
        } else if (preg_match('/^zone=ip-[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}/', $zone)) {
            return explode('zone=ip-', $zone)[1];
        }

        return '';
    }

    /**
     * @param $password
     * @param $challenge
     * @param $uamsecret
     * @return string
     */
    static public function xirrusEncrypting($password, $challenge, $uamsecret)
    {
        $hex_chal       = pack('H32', $challenge);                      //Hex the challenge
        $newchal        = pack('H*', md5($hex_chal.$uamsecret));    //Add it to with $uamsecret (shared between chilli an this script)
        $newpwd         = pack('a32', $password);                       //pack again
        $md5pwd         = "";
        $unpacked_pass  = "";

        for ($i = 0; $i < 4; $i++) {

            $start = $i*16;

            if($start < strlen($newpwd)){
                $substring      = substr($newpwd, $start);
                $unpacked_pass  = implode ('', unpack('H32', ( $substring ^ $newchal)) ); //unpack again
            }

            $md5pwd .= $unpacked_pass;

        }

        return $md5pwd;
    }
}
