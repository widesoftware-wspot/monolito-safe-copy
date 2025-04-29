<?php

namespace Wideti\PanelBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\SmsCredit;
use Wideti\DomainBundle\Entity\SmsCreditHistoric;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\Sms\SmsGatewayService;
use Wideti\DomainBundle\Service\SmsCredit\SmsCreditService;
use Wideti\PanelBundle\Form\Type\SmsCreditType;
use Wideti\PanelBundle\Form\Type\SmsGatewayType;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\Request;

class SmsCreditController
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
     * @var SmsCreditService
     */
    private $smsCreditService;

    /**
     * SmsCreditController constructor.
     * @param EntityManager $em
     * @param FrontendControllerHelper $controllerHelper
     * @param SmsCreditService $smsCreditService
     */
    public function __construct(
        EntityManager $em,
        FrontendControllerHelper $controllerHelper,
        SmsCreditService $smsCreditService
    ) {
        $this->em = $em;
        $this->controllerHelper = $controllerHelper;
        $this->smsCreditService = $smsCreditService;
    }

    public function editAction(Request $request)
    {
        $client = $this->em->getRepository("DomainBundle:Client")->findOneBy(['id' => $request->get('client')]);

        $entity = $this->em->getRepository("DomainBundle:SmsCredit")->findOneBy(["client" => $client->getId()]) ?: new SmsCredit();
        $form = $this->controllerHelper->createForm(SmsCreditType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setClient($client->getId());
            $this->smsCreditService->add($entity, $form->get("totalAvailable")->getData());
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_sms_credit', ['client' => $client->getId()]));
        }

        $historic = $this->smsCreditService->getHistoric($client);

        return $this->render(
            'PanelBundle:SmsCredit:edit.html.twig',
            [
                'entity'    => $entity,
                'form'      => $form->createView(),
                'historic'  => $historic
            ]
        );
    }

    public function deleteHistoricAction(Request $request)
    {
        $historicId = $request->get('id');

        try {
            $historic = $this->smsCreditService->getHistoricById($historicId);
            $this->smsCreditService->remove($historic);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'type'    => 'success',
                    'message' => 'Registro removido com sucesso'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'type'    => 'error',
                'message' => 'Ocorreu um erro ao remover o registro.'
            ]);
        }
    }
}
