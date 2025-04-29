<?php

namespace Wideti\PanelBundle\Controller;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Sms\SmsGatewayService;
use Wideti\PanelBundle\Form\Type\SmsGatewayType;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\Request;

class SmsGatewayController
{
    use TwigAware;
    use FormAware;
    use RouterAware;
    use LoggerAware;
    use FlashMessageAware;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var SmsGatewayService
     */
    private $gatewayService;

    /**
     * SmsGatewayController constructor.
     * @param EntityManager $em
     * @param FrontendControllerHelper $controllerHelper
     * @param SmsGatewayService $gatewayService
     */
    public function __construct(
        EntityManager $em,
        FrontendControllerHelper $controllerHelper,
        SmsGatewayService $gatewayService
    ) {
        $this->em = $em;
        $this->controllerHelper = $controllerHelper;
        $this->gatewayService = $gatewayService;
    }

    public function editAction(Request $request)
    {
        $gateway = $this->em->getRepository('DomainBundle:SmsGateway')->findAll()[0];

        $form = $this->controllerHelper->createForm(
            SmsGatewayType::class,
            $gateway
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->gatewayService->update($gateway);
            } catch (\Exception $e) {
                die($e);
            }
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_sms_gateway_edit'));
        }

        return $this->render(
            'PanelBundle:SmsGateway:edit.html.twig',
            [
                'entity' => $gateway,
                'form'   => $form->createView()
            ]
        );
    }

}
