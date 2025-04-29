<?php

namespace Wideti\AdminBundle\Controller;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientStatusService;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogsService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class CustomerAreaController
{
    const MAIL = 'cancelamento@wspot.com.br';
 
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ModuleService
     */
    private $moduleService;
    /**
     * @var ErpService
     */
    private $erpService;

    private $superlogicaDomain;
    /**
     * @var ClientStatusService
     */
    private $clientStatusService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ClientLogsService
     */
    private $clientLogsService;
    /**
     * @var MailMessageBuilder
     */
    private $mailMessageBuilder;

	/**
	 * CustomerAreaController constructor.
	 * @param AdminControllerHelper $controllerHelper
	 * @param ModuleService $moduleService
	 * @param ErpService $erpService
	 * @param ClientStatusService $clientStatusService
	 * @param ClientLogsService $clientLogsService
	 * @param Logger $logger
	 * @param $superlogicaDomain
	 */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        ModuleService $moduleService,
        ErpService $erpService,
        ClientStatusService $clientStatusService,
        ClientLogsService $clientLogsService,
        Logger $logger,
        $superlogicaDomain
    ) {
        $this->controllerHelper    = $controllerHelper;
        $this->moduleService       = $moduleService;
        $this->erpService          = $erpService;
        $this->clientStatusService = $clientStatusService;
        $this->clientLogsService   = $clientLogsService;
        $this->logger              = $logger;
	    $this->superlogicaDomain   = str_replace(["'", "\""], "", $superlogicaDomain);
	    $this->mailMessageBuilder  = new MailMessageBuilder();
    }

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function indexAction()
    {
        if (!$this->moduleService->modulePermission('customer_area')) {
            return $this->render('AdminBundle:Admin:modulePermission.html.twig');
        }

        /**
         * @var $client Client
         */
        $client = $this->session->get('wspotClient');

        return $this->render(
            'AdminBundle:CustomerArea:index.html.twig',
            [
                'superlogicaDomain' => $this->superlogicaDomain,
                'erpToken'  => $this->erpService->getToken($this->getUser()),
                'client' => $client,
                'clientStatus' => $client->getStatusAsString()
            ]
        );
    }

    public function cancelAction(Request $request)
    {
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $this->getLoggedClient()
        ]);

        $message = 'Cancelamento solicitado com sucesso';

        if ($client) {
            try {
                $client->setCancellationReason($request->get('reason'));
                $this->em->flush();
                $this->sendCancelNotificationEmail($client);
            } catch (\Exception $ex) {
                $this->logger->addCritical('Fail to cancel client account', [
                    'errorMessage' => $ex->getMessage(),
                    'trace' => $ex->getTraceAsString()
                ]);

                $message = 'Falha ao solicitar o cancelamento';
            }
        } else {
            $this->logger->addCritical('Fail to cancel client account', [
                'errorMessage' => 'Client not found on cancel account process.',
            ]);

            $message = 'Cliente não encontrado';
        }

        return new JsonResponse([
            'msg' => $message
        ]);
    }

    private function sendCancelNotificationEmail(Client $client)
    {
        $this->mailerService->send(
            $this->mailMessageBuilder
                ->subject("Cancelamento - Cliente {$client->getDomain()}")
                ->from(["Cancelamento - {$client->getDomain()}" => $this->emailHeader->getSender()])
                ->to([ [self::MAIL] ])
                ->htmlMessage(
                    $this->renderView(
                        "AdminBundle:Client:emailCancelationRequest.html.twig", [
                            'client'    => $client,
                            'requester' => $this->securityContext->getToken()->getUsername()
                        ]
                    )
                )
                ->build()
        );
    }
}