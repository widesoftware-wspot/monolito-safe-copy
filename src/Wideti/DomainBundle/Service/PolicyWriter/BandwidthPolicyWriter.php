<?php

namespace Wideti\DomainBundle\Service\PolicyWriter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\ConfigurationValue;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\FrontendBundle\Factory\Nas;

class BandwidthPolicyWriter implements PolicyWriter
{
    const CONFIG_BANDWIDTH = 'bandwidth';

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * BandwidthPolicyWriter constructor.
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param Nas $nas
     * @param Guest $guest
     * @param Client $client
     * @param RadiusPolicyBuilder $builder
     * @return void
     */
    public function write(Nas $nas, Guest $guest, Client $client, RadiusPolicyBuilder $builder)
    {
        $configValues = $this->getDonwloadUpload($guest);

        $builder->withBandwidthPolicy(
            isset($configValues['download']) ? $configValues['download'] : 0,
            isset($configValues['upload']) ? $configValues['upload'] : 0,
            isset($configValues['hasLimit']) ? $configValues['hasLimit'] : false
        );
    }

    /**
     * @param Guest $guest
     * @return array
     */
    private function getDonwloadUpload(Guest $guest)
    {
        $group = $this
            ->documentManager
            ->getRepository('DomainBundle:Group\Group')
            ->findOneBy([
                'shortcode' => $guest->getGroup()
            ]);

        if (!empty($group)) {
            $bandwidthConfig = $this->getConfigurationBandwidth($group);
            return $this->getConfigurationValues($bandwidthConfig);
        }
    }

    /**
     * @param Group $guestGroup
     * @return null| Configuration
     */
    private function getConfigurationBandwidth(Group $guestGroup)
    {
        foreach ($guestGroup->getConfigurations() as $config) {
            if ($config->getShortcode() === self::CONFIG_BANDWIDTH) {
                return $config;
            }
        }
        return null;
    }

    private function getConfigurationValues(Configuration $config)
    {
        /**
         * @var PersistentCollection $configValues
         */
        $configValues = $config->getConfigurationValues();

        $configs = array_reduce($configValues->toArray(), function($carry, ConfigurationValue $config) {
            $carry[$config->getKey()] = $config->getValue();
            return $carry;
        }, []);

        return [
            'hasLimit' => !empty($configs['enable_bandwidth']) ? true : false,
            'download' => $configs['bandwidth_download_limit'] ?: 0,
            'upload' => $configs['bandwidth_upload_limit'] ?: 0,
        ];
    }
}
