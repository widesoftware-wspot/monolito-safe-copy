<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\ApiBundle\Helpers\Builder\SmsCallbackBuilder;
use Wideti\ApiBundle\Helpers\Dto\SmsCallbackDto;
use Wideti\DomainBundle\Service\Client\SelectClientByDomainService;
use Wideti\DomainBundle\Service\Sms\SmsHistoryImp;
use Wideti\DomainBundle\Service\Sms\Wavy;

class InternalSmsController implements ApiResource
{
    const RESOURCE_NAME = 'internal_sms';

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var SelectClientByDomainService
     */
    private $selectClientByDomain;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var SmsHistoryImp
     */
    private $smsHistory;

    /**
     * InternalSmsController constructor.
     * @param EntityManager $em
     * @param SelectClientByDomainService $selectClientByDomain
     * @param Logger $logger
     * @param SmsHistoryImp $smsHistory
     */
    public function __construct(
        EntityManager $em,
        SelectClientByDomainService $selectClientByDomain,
        Logger $logger,
        SmsHistoryImp $smsHistory
    ) {
        $this->em = $em;
        $this->selectClientByDomain = $selectClientByDomain;
        $this->logger = $logger;
        $this->smsHistory = $smsHistory;
    }

    public function callbackAction(Request $request)
    {
        $content = $request->getContent();

        if (!$content) {
            return new JsonResponse(["error" => "Content body is empty"], 400);
        }

        /**
         * @var $smsDto SmsCallbackDto
         */
        $smsDto = $this->buildDto($content);

        if ($smsDto->getSentStatus() !== Wavy::SUCCESS_SENT_STATUS) {
            $this->logger->addCritical(
                "Critical - Fail to send sms by Wavy",
                [
                    'content' => json_decode(json_encode($smsDto), true)
                ]
            );
        }

        try {
            $this->smsHistory->updateHistoryWithCallback($smsDto);
        } catch (\Exception $ex) {
            $this->logger->addError(
                "Wavy - Fail to update Sms History with callback: {$ex->getMessage()}",
                [
                   "smsDto" => json_decode(json_encode($smsDto), true)
                ]
            );
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }

        return new JsonResponse($smsDto, 200);
    }

    private function buildDto($requestContent)
    {
        $callback = json_decode($requestContent, true);
        $builder  = new SmsCallbackBuilder();

        return $builder
            ->withSender(SmsCallbackDto::SENDER_WAVY)
            ->withId($callback["correlationId"])
            ->withCarrierName(isset($callback["carrierName"]) ? $callback["carrierName"] : "N/I")
            ->withDestination($callback["destination"])
            ->withSentStatusCode(isset($callback["sentStatusCode"]) ? $callback["sentStatusCode"] : 0)
            ->withSentStatus($callback["sentStatus"])
            ->withDeliveredStatus(isset($callback["deliveredStatus"]) ? $callback["deliveredStatus"] : "N/I")
            ->withDeliveredDate(
                isset($callback["deliveredDate"])
                    ? new \DateTime(date("Y-m-d H:i:s", strtotime($callback["deliveredDate"])))
                    : new \DateTime("NOW")
            )
            ->build();
    }

    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
