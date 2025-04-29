<?php

namespace Wideti\DomainBundle\Service\SmsCredit;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\SmsCredit;
use Wideti\DomainBundle\Entity\SmsCreditHistoric;
use Wideti\DomainBundle\Exception\SmsCreditException;

class SmsCreditServiceImp implements SmsCreditService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * SmsCreditImp constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function add(SmsCredit $credit, $creditAmount)
    {
        $existentSmsCredit = $this->getAvailableClientCredit($credit->getClient());

        if ($existentSmsCredit) {
            $credit = $existentSmsCredit;
            $total = $creditAmount + $credit->getTotalAvailable();
            $credit->setTotalAvailable($total);
        } else {
            $credit->setTotalAvailable($creditAmount);
        }

        $this->em->persist($credit);
        $this->em->flush();

        $this->addHistoric($credit, $creditAmount, SmsCreditHistoric::OPERATION_PURCHASED);
    }

    public function remove(SmsCreditHistoric $historic)
    {
        $existentSmsCredit = $this->getAvailableClientCredit($historic->getClient());

        if ($existentSmsCredit) {
            $total = $existentSmsCredit->getTotalAvailable() - $historic->getQuantity();
            $existentSmsCredit->setTotalAvailable($total);
            $this->em->persist($existentSmsCredit);
        }

        $this->removeHistoric($historic);
        $this->em->flush();
    }

    private function addHistoric(SmsCredit $credit, $newCreditAmount, $operation)
    {
        $historic = new SmsCreditHistoric();
        $historic->setClient($credit->getClient());
        $historic->setQuantity($newCreditAmount);
        $historic->setOperation($operation);
        $this->em->persist($historic);
        $this->em->flush();
    }

    private function removeHistoric(SmsCreditHistoric $historic)
    {
        $this->em->remove($historic);
    }

    public function getHistoric(Client $client)
    {
        $purchasedHistoric = $this->em->getRepository("DomainBundle:SmsCreditHistoric")->findBy([
            'client' => $client,
            'operation' => SmsCreditHistoric::OPERATION_PURCHASED
        ]);

        $usedHistoric = $this->em->getRepository("DomainBundle:SmsCreditHistoric")->findBy([
            'client' => $client,
            'operation' => SmsCreditHistoric::OPERATION_USED
        ]);

        return [
            "purchased" => $purchasedHistoric,
            "used" => $usedHistoric
        ];
    }

    public function getHistoricById($id)
    {
        return $this->em->getRepository("DomainBundle:SmsCreditHistoric")->findOneById($id);
    }

    /**
     * @param $clientId
     * @return SmsCredit|null
     */
    public function getAvailableClientCredit($clientId)
    {
        return $this->em->getRepository("DomainBundle:SmsCredit")->findOneBy(["client" => $clientId]);
    }

    public function checkIfClientHasEnoughCreditAvailable(Client $client, $totalSmsToSend)
    {
        $credit = $this->getAvailableClientCredit($client->getId());

        if (!$credit || $credit->getTotalAvailable() < $totalSmsToSend) {
            throw new SmsCreditException("Insufficient credit!");
        }
    }

    public function consume(Client $client, $totalConsumedSms)
    {
        $credit = $this->getAvailableClientCredit($client->getId());
        $this->addHistoric($credit, $totalConsumedSms, SmsCreditHistoric::OPERATION_USED);

        $total = $credit->getTotalAvailable() - $totalConsumedSms;
        $credit->setTotalAvailable($total);
        $this->em->persist($credit);
        $this->em->flush();
    }
}
