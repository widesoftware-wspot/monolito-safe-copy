<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Rhumsaa\Uuid\Uuid;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;

class ClientChangePlanController
{
    /**
     * @var ClientService
     */
    private $clientService;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ControllerHelper
     */
    private $controllerHelper;

    /**
     * @var $apiPurchaseWSpotUrl
     */
    private $apiPurchaseWSpotUrl;

    /**
     * ClientChangePlanController constructor.
     * @param ClientService $clientService
     * @param EntityManager $em
     * @param ControllerHelper $controllerHelper
     * @param $apiPurchaseWSpotUrl
     */
    public function __construct(
        ClientService $clientService,
        EntityManager $em,
        ControllerHelper $controllerHelper,
        $apiPurchaseWSpotUrl
    ) {
        $this->clientService = $clientService;
        $this->em = $em;
        $this->controllerHelper = $controllerHelper;
        $this->apiPurchaseWSpotUrl = $apiPurchaseWSpotUrl;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function redirectChangePlanAction()
    {
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $this->clientService->getLoggedClient()->getId()
        ]);

        if ($client->getStatus() !== Client::STATUS_POC) {
            return $this->controllerHelper->redirectToRoute('admin_dashboard');
        }

        $uuid = Uuid::uuid4();
        $hash = md5($uuid->toString());

        $client->setChangePlanHash($hash);
        $this->em->persist($client);
        $this->em->flush();

        $redirectUrl = "{$this->apiPurchaseWSpotUrl}?token={$hash}";

        return $this->controllerHelper->redirect($redirectUrl);
    }

}