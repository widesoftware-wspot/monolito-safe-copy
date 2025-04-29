<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\Notification\Dto\Message;
use Wideti\DomainBundle\Service\Notification\NotificationService;

class NotificationController
{
    /**
     * @var NotificationService
     */
    private $notificationService;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * NotificationController constructor.
     * @param NotificationService $notification
     * @param EntityManager $em
     */
    public function __construct(NotificationService $notification, EntityManager $em)
    {
        $this->notificationService = $notification;
        $this->em = $em;
    }

    public function wrongApConfigNotificationAction(Request $request)
    {
        $message = \Aws\Sns\MessageValidator\Message::fromRawPostData();
        $data = $message->get('Message');

        if ($message->get('Type') == 'SubscriptionConfirmation') {
            $guzzle = new \GuzzleHttp\Client();
            $guzzle->request("GET", $message->get('SubscribeURL'));
            header("Status: 200");
            exit;
        }
        $params = json_decode($data, true);
        $message = $this->getMessage($params);
        $client = $this->getClient($params);

        $this->notificationService->notify($client, $message);

        return new JsonResponse(['message' => 'ok'], 200);
    }

    /**
     * @param array $data
     * @return Message
     */
    private function getMessage(array $data)
    {
        $message = isset($data['message']) ? $data['message'] : '';
        $type = isset($data['type']) ? $data['type'] : '';
        return new Message($type, $message);
    }

    /**
     * @param array $data
     * @return Client
     */
    private function getClient(array $data)
    {
        $clientId = isset($data['client_id']) ? $data['client_id'] : 0;

        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            "id" => $clientId
        ]);

        if (!$client) {
            throw new ClientNotFoundException('Cliente não encontrado');
        }
        return $client;
    }
}

