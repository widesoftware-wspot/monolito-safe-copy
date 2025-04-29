<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\WSpotFaker\WSpotFakerService;

class WSpotFakerController
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var WSpotFakerService
     */
    private $faker;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var ClientService
     */
    private $clientService;

    /**
     * WSpotFakerController constructor.
     * @param EntityManager $em
     * @param AdminControllerHelper $controllerHelper
     * @param WSpotFakerService $faker
     * @param Session $session
     * @param ClientService $clientService
     */
    public function __construct(
        EntityManager $em,
        AdminControllerHelper $controllerHelper,
        WSpotFakerService $faker,
        Session $session,
        ClientService $clientService
    ) {
        $this->em = $em;
        $this->controllerHelper = $controllerHelper;
        $this->faker = $faker;
        $this->session = $session;
        $this->clientService = $clientService;
    }

    public function executeAction($action)
    {
        $status = ($action == 'create') ? true : false;

        /**
         * @var $client Client
         */
        $client = $this->session->get('wspotClient');
        if (!$client->allowFakeData()){
            throw new AccessDeniedHttpException("Este cliente não possui permissão para gerar dados falsos.");
        }

        $client->setFakeMode($status);
        $client = $this->em->merge($client);
        $this->em->flush();

        $this->faker->execute($client, $action);

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_dashboard'));
    }
}
