<?php

namespace Wideti\DomainBundle\Document\Group\Factory;

use Wideti\DomainBundle\Document\Group\Builder\GroupConfigurationBuilder;
use Wideti\DomainBundle\Document\Group\Builder\GroupConfigurationValuesBuilder;
use Wideti\DomainBundle\Document\Group\Configuration;

class FactoryDefaultGroupConfigurations
{

    const BLOCK_PER_TIME    = 'getBlockPerTime';
    const VALIDITY_ACCESS   = 'getValidityAccess';
    const BANDWIDTH_LIMIT   = 'getBandwidth';

    /**
     * @param $configuration
     * @return Configuration
     */
    public static function get($configuration)
    {
        $factory = new FactoryDefaultGroupConfigurations();
        return $factory->$configuration();
    }

    public function getBandwidth()
    {
        $enableBluider = new GroupConfigurationValuesBuilder();
        $enable = $enableBluider
            ->withKey('enable_bandwidth')
            ->withValue(false)
            ->withType('checkbox')
            ->withLabel('Ativar')
            ->withParams([])
            ->build();

        $downloadBuilder = new GroupConfigurationValuesBuilder();
        $download = $downloadBuilder
            ->withKey('bandwidth_download_limit')
            ->withValue('')
            ->withType('choice')
            ->withParams([
                "choices" => [
                    '65536'     => '64 Kbps',
                    '131072'    => '128 Kbps',
                    '262144'    => '256 Kbps',
                    '524288'    => '512 Kbps',
                    '1048576'   => '1 Mbps',
                    '2097152'   => '2 Mbps',
                    '3145728'   => '3 Mbps',
                    '5242880'   => '5 Mbps',
                    '10485760'  => '10 Mbps',
                    '20971520'  => '20 Mbps',
                    '31457280'  => '30 Mbps',
                    '41943040'  => '40 Mbps',
                    '52428800'  => '50 Mbps'
                ]
            ])
            ->withLabel('Download')
            ->build();

        $uploadBuilder = new GroupConfigurationValuesBuilder();
        $upload = $uploadBuilder
            ->withKey('bandwidth_upload_limit')
            ->withValue('')
            ->withType('choice')
            ->withLabel('Upload')
            ->withParams([
                "choices" => [
                    '65536'     => '64 Kbps',
                    '131072'    => '128 Kbps',
                    '262144'    => '256 Kbps',
                    '524288'    => '512 Kbps',
                    '1048576'   => '1 Mbps',
                    '2097152'   => '2 Mbps',
                    '3145728'   => '3 Mbps',
                    '5242880'   => '5 Mbps',
                    '10485760'  => '10 Mbps',
                    '20971520'  => '20 Mbps',
                    '31457280'  => '30 Mbps',
                    '41943040'  => '40 Mbps',
                    '52428800'  => '50 Mbps'
                ]
            ])
            ->build();

        $configBuilder = new GroupConfigurationBuilder();
        return $configBuilder
            ->withCategory('Limite de banda')
            ->withShortcode('bandwidth')
            ->withDescription("Controle o limite de banda (download e upload) do visitante")
            ->addConfigurarionValue($enable)
            ->addConfigurarionValue($download)
            ->addConfigurarionValue($upload)
            ->build();
    }

    public function getValidityAccess()
    {
        $enableBuilder = new GroupConfigurationValuesBuilder();
        $enable = $enableBuilder
            ->withKey('enable_validity_access')
            ->withValue(false)
            ->withType('checkbox')
            ->withLabel('Ativar validade de acesso')
            ->withParams([])
            ->build();

        $accessLimitBuilder = new GroupConfigurationValuesBuilder();
        $accessLimit = $accessLimitBuilder
            ->withKey('validity_access_date_limit')
            ->withValue('')
            ->withType('text')
            ->withLabel('Data limite de acesso')
            ->withParams([
                'attr' => [
                    'class' => 'mask-date'
                ]
            ])
            ->build();

        $configBuilder = new GroupConfigurationBuilder();
        return $configBuilder
            ->withCategory("Validade de acesso")
            ->withShortcode('validity_access')
            ->withDescription("Data máxima que o visitante poderá acessar a plataforma")
            ->addConfigurarionValue($enable)
            ->addConfigurarionValue($accessLimit)
            ->build();
    }

    /**
     * @return \Wideti\DomainBundle\Document\Group\Configuration
     */
    public function getBlockPerTime()
    {
        $enableBlockPerTimeBuilder = new GroupConfigurationValuesBuilder();
        $enableTimeBlock = $enableBlockPerTimeBuilder
            ->withKey('enable_block_per_time')
            ->withValue(false)
            ->withType('checkbox')
            ->withLabel("Ativar")
            ->withParams([])
            ->build();

        $blockTimeBuilder = new GroupConfigurationValuesBuilder();
        $blockTime = $blockTimeBuilder
            ->withKey('block_per_time_time')
            ->withValue('')
            ->withType('text')
            ->withLabel("Tempo")
            ->withTip("Informar o limite de tempo que o visitante poderá utilizar a Internet. O valor informado será renovado dentro do período definido no campo abaixo.")
            ->withParams([])
            ->build();

        $blockPeriodBuilder = new GroupConfigurationValuesBuilder();
        $blockPeriod = $blockPeriodBuilder
            ->withKey('block_per_time_period')
            ->withValue('')
            ->withType('text')
            ->withLabel("Período")
            ->withTip("Informar o período no qual o limite de tempo será considerado. Caso o campo acima tenha o valor de 1h, o período poderá ser de 1d (1 dia) fazendo com que o visitante tenha somente 1 hora de acesso por dia.")
            ->withParams([])
            ->build();

        $blockPerTimeBuilder = new GroupConfigurationBuilder();
        return $blockPerTimeBuilder
            ->withCategory("Bloqueio por tempo")
            ->withDescription("Limite de tempo que o visitante pode permanecer online")
            ->withShortcode("block_per_time")
            ->addConfigurarionValue($enableTimeBlock)
            ->addConfigurarionValue($blockTime)
            ->addConfigurarionValue($blockPeriod)
            ->build();
    }
}
