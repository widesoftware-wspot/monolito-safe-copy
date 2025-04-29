<?php

namespace Wideti\DomainBundle\Service\Timezone;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Repository\TimezoneRepository;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;

class TimezoneService
{
    const UTC = 'UTC';
    const DEFAULT_TIMEZONE = 'America/Sao_Paulo';

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var AccessPointsService $accessPointService
     */
    private $accessPointService;

    /**
     * TimezoneService constructor.
     * @param EntityManager $em
     * @param AccessPointsService $accessPointService
     */
    public function __construct(
        EntityManager $em,
        AccessPointsService $accessPointService
    ) {
        $this->em = $em;
        $this->accessPointService = $accessPointService;
    }

    /**
     * @return array|\Wideti\DomainBundle\Entity\Zone[]
     */
    public function getAllBrazilianTimezones()
    {
        return $this->em->getRepository('DomainBundle:Zone')->queryAllBrazilianTimezones();
    }

    /**
     * @return array|\Wideti\DomainBundle\Entity\Zone[]
     */
    public function getAllTimezonesExceptBrazilian()
    {
        return $this->em->getRepository('DomainBundle:Zone')->queryAllTimezonesExceptBrazilian();
    }

    /**
     * @return array|\Wideti\DomainBundle\Entity\Zone[]
     */
    public function getAllTimezones()
    {
        return $this->em->getRepository('DomainBundle:Zone')->findAll();
    }

    /**
     * @param $registrationMacAddress
     * @return string
     */
    public function getAccessPointTimezone($registrationMacAddress)
    {
        $accessPoint = $this->em->getRepository('DomainBundle:AccessPoints')
            ->findOneBy(['identifier' => $registrationMacAddress]);

        return empty($accessPoint) ? self::DEFAULT_TIMEZONE : $accessPoint->getTimezone();
    }

    /**
     * @param $timezone
     * @return null|object|\Wideti\DomainBundle\Entity\Zone
     */
    public function getTimezoneByZoneName($timezone) {
        return $this->em->getRepository('DomainBundle:Zone')
            ->findOneBy(['zoneName' => $timezone]);
    }
}
