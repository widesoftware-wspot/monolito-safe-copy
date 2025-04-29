<?php

namespace Wideti\DomainBundle\Service\Sms;

use Doctrine\ORM\EntityManager;
use Wideti\ApiBundle\Helpers\Dto\SmsCallbackDto;

class SmsHistoryImp implements SmsHistory
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * SmsHistoryImp constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param SmsCallbackDto $callbackDto
     * @throws \Doctrine\DBAL\DBALException
     * TODO foi feito dessa forma (update via statement) pois do jeito que estava antes (consultando o objeto e fazendo
     * TODO o update via entity manager) estava demorando certa de 4 segundos para executar a ação.
     */
    public function updateHistoryWithCallback(SmsCallbackDto $callbackDto)
    {
        $messageId       = $callbackDto->getId();
        $sender          = $callbackDto->getSender();
        $carrier         = $callbackDto->getCarrierName();
        $sentStatusCode  = $callbackDto->getSentStatusCode();
        $sentStatus      = $callbackDto->getSentStatus();
        $deliveredStatus = $callbackDto->getDeliveredStatus();
        $deliveredDate   = date_format($callbackDto->getDeliveredDate() ?: new \DateTime("NOW"), 'Y-m-d H:i:s');

        $conn = $this->em->getConnection();

        $query = "
            UPDATE sms_historic
            SET sender = '{$sender}',
            carrier = '{$carrier}',
            message_status_code = {$sentStatusCode},
            message_status = '{$sentStatus}',
            delivered_status = '{$deliveredStatus}',
            delivered_date = '{$deliveredDate}'
            WHERE id = {$messageId}
        ";

        $statement  = $conn->prepare($query);
        $statement->execute();
    }
}
