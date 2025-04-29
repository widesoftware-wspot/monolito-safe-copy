<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\SMSBillingControl;
use Wideti\DomainBundle\Repository\SMSBillingControlRepository;

class CreateBillingService
{
    /**
     * @var SMSBillingControlRepository
     */
    private $SMSBillingControlRepository;

    /**
     * CreateBillingService constructor.
     * @param SMSBillingControlRepository $SMSBillingControlRepository
     */
    public function __construct(SMSBillingControlRepository $SMSBillingControlRepository)
    {
        $this->SMSBillingControlRepository = $SMSBillingControlRepository;
    }

    /**
     * @param Client $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @param $sms
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(Client $client, $closingDateStart, $closingDateEnd, $sms)
    {
        $smsBillingControl = new SMSBillingControl();
        $smsBillingControl->setAmountToPay($sms['amount_to_pay']);
        $smsBillingControl->setClient($client);
        $smsBillingControl->setClosingDateEnd($closingDateEnd);
        $smsBillingControl->setClosingDateStart($closingDateStart);
        $smsBillingControl->setCostPerSms($sms['cost_per_sms']);
        $smsBillingControl->setSentSmsNumber($sms['sent_sms_number']);
        $smsBillingControl->setRegisteredIn(date('Y-m-d'));
        $smsBillingControl->setClosingDateReference(
            substr($closingDateEnd, 0, 4) . '-' .
            substr($closingDateEnd, 5, 2) . '-' .
            str_pad($client->getClosingDate(), 2, 0, STR_PAD_LEFT)
        );

        if ((int)$sms['sent_sms_number'] > 0) {
            $status = SMSBillingControl::STATUS_PENDING;
        } else {
            $status = SMSBillingControl::STATUS_BILLED;
        }

        $smsBillingControl->setStatus($status);

        $this->SMSBillingControlRepository->create($smsBillingControl);
    }
}