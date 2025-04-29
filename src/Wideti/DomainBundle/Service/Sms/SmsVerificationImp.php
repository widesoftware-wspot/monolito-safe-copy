<?php

namespace Wideti\DomainBundle\Service\Sms;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class SmsVerificationImp implements SmsVerification
{
    /**
     * @var EntityManagerAware
     */
    private $em;
    /**
     * @var Session
     */
    private $session;

    public function __construct(
        $maxNumberSmsSendingPoc,
        EntityManager $em,
        Session $session
    ) {
        $this->maxNumberSmsSendingPoc   = $maxNumberSmsSendingPoc;
        $this->em                       = $em;
        $this->session                  = $session;
    }

    public function checkLimitSendSms()
    {
        $client = $this->session->get('wspotClient');

        if ($client->getStatus() == Client::STATUS_POC) {
            $totalSend = $this->em
                ->getRepository('DomainBundle:SmsHistoric')
                ->getSmsBillingByClient($client)
            ;

            if (count($totalSend) == ($this->maxNumberSmsSendingPoc-1)) {
                return false;
            }

            if (count($totalSend) >= $this->maxNumberSmsSendingPoc) {
                return true;
            }
        }

        return false;
    }
}
