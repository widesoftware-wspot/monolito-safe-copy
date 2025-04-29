<?php

namespace Wideti\DomainBundle\Service\Sms;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\SmsGateway;

class SmsGatewayServiceImp implements SmsGatewayService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * SmsGatewayServiceImp constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function update(SmsGateway $gateway)
    {
        $this->em->persist($gateway);
        $this->em->flush();
        return $gateway;
    }

    public function activeGateway()
    {
        return $this->em->getRepository('DomainBundle:SmsGateway')->findAll()[0]->getGateway();
    }
}
