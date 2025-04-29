<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class GuestPasswordRecoveryController
{
    use MongoAware;
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
     * ClientChangePlanController constructor.
     * @param ClientService $clientService
     * @param EntityManager $em
     * @param ControllerHelper $controllerHelper
     */
    public function __construct(
        ClientService $clientService,
        EntityManager $em,
        ControllerHelper $controllerHelper
    ) {
        $this->clientService    = $clientService;
        $this->em               = $em;
        $this->controllerHelper = $controllerHelper;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);
        if (empty($requestContent) ||
            !array_key_exists('name', $requestContent) ||
            !array_key_exists('value', $requestContent)) {
            return new JsonResponse(['message' => 'Request body is empty or invalid. Keys (name) and (value) is required'], 400);
        } 

        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $this->clientService->getLoggedClient()->getId()
        ]);

        $name   = $requestContent['name'];
        $value  = $requestContent['value'];
        
        if ($name == 'guest_password_recovery_security') {
            $client->setGuestPasswordRecoverySecurity($value);

        } else if ($name == 'guest_password_recovery_email') {
            $client->setGuestPasswordRecoveryEmail($value);

        } else {
            return new JsonResponse(['status' => '500', 'message' => 'O name ('.$name.') e value ('.var_export($value, true).') são inválidos'], 400);
        }

        $this->em->persist($client);
        $this->em->flush();

        return new JsonResponse(['status' => '200'], 200);
    }
}


