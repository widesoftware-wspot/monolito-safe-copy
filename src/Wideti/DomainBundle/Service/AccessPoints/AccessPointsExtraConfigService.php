<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\AccessPointExtraConfig;
use Wideti\DomainBundle\Entity\AccessPoints;

class AccessPointsExtraConfigService
{
    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param AccessPoints $ap
     * @param string $extraConfigValue
     * @return void
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create($ap, $extraConfigValue)
    {
        $extraConfig = new AccessPointExtraConfig();
        $extraConfig->setAp($ap);
        if ($extraConfigValue != '') {
            $extraConfig->setConfigType($this->getExtraConfigType($ap));
            $extraConfig->setValue($extraConfigValue);
            $this->em->persist($extraConfig);
        }

        try {
            $this->em->flush();
        } catch (DBALException $e) {
            throw new DBALException($e->getMessage());
        }

    }

    private function getExtraConfigType($ap) {
        $configType = $this->getExtraConfigTypeKey($ap->getVendor());
        try {
            $extraConfig = $this->em
                ->getRepository('DomainBundle:ExtraConfigType')
                ->findOneBy([
                    'configType' => $configType
                ]);
        } catch (DBALException $e) {
            throw new DBALException($e->getMessage());
        }
        return $extraConfig;
    }


    private function getExtraConfigTypeKey($vendor) {
        if ($vendor == 'ruckus-cloud') {
            return 'secretKey';
        } elseif (in_array($vendor, ['tp-link-cloud', 'tp-link-v4-cloud', 'tp-link-v5-cloud', 'unifi-ubiquiti', 'unifi-controller-cadastro'])) {
            return 'controllerUrl';
        } else {
            return null;
        }
    }


    /**
     * @param AccessPoints $ap
     * @return object|AccessPointExtraConfig|null
     * @throws DBALException
     */
    public function findExtraConfigByAp(AccessPoints $ap)
    {
        try {
            $config = $this->em
                ->getRepository('DomainBundle:AccessPointExtraConfig')
                ->findOneBy([
                    'ap' => $ap
                ]);
        } catch (DBALException $e) {
            throw new DBALException($e->getMessage());
        }
        return $config;
    }

    /**
     * @param AccessPoints $ap
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteExtraConfig(AccessPoints $ap)
    {
        $conf = $this->findExtraConfigByAp($ap);
        $this->em
            ->remove($conf);

        $this->em->flush();
    }

    /**
     * @param AccessPointExtraConfig $accessPointExtraConfig
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(AccessPointExtraConfig $accessPointExtraConfig)
    {
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();


        $this->em->persist($accessPointExtraConfig);
        $this->em->flush();
    }
}