<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\DomainBundle\Service\Module\ModuleAware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Ixc\IxcService;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\AdminBundle\Form\IxcIntegrationType;



class IxcIntegrationController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use CustomFieldsAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    /**
     * @var IxcsoftService
     */
    private $IxcService;

    /**
     * cfIntegrationController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param IxcService $IxcService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        IxcService $IxcService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->IxcService       = $IxcService;
    }

    /**
	 * @param Request $request
	 * @return Response
	 */
    public function testCredentialsAction(Request $request) {
        if ($request->getMethod() !== "POST") {
		    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl("frontend_index"));
	    }
        $client = $this->getLoggedClient();

	    try {
            $data = $request->request->all();
            $clientSecret = $this->getModuleConfigurationValue($client, 'Ixc_client_secret'); #token
            $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
            $newClientSecret = $data['client_secret'];
            $newHost = $data['host'];
            $this->updateModuleConfigurationValue($clientSecret, $newClientSecret);
            $this->updateModuleConfigurationValue($host, $newHost);
            $this->em->flush();
            $credentialsOk = $this->IxcService->testCredentials($client);
            if($credentialsOk) {
                return new JsonResponse([
                    "message" => "Credenciais ok!"
                ]);
            } else {
                return new JsonResponse([
                    "message" => "Erro ao validar, cheque as credenciais e tente novamente"
                ], 400);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                "Erro ao validar, cheque as credenciais e tente novamente"
            ], 500);
        }
    }


	/**
	 * @param Request $request
	 * @return Response
	 */
    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('Ixc_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $clientSecret = $this->getModuleConfigurationValue($client, 'Ixc_client_secret');
        $enableIxc = $this->getModuleConfigurationValue($client, 'enable_Ixc_integration');
        $enableIxcAuth = $this->getModuleConfigurationValue($client, 'enable_Ixc_authentication');
        $enableIxcProspect = $this->getModuleConfigurationValue($client, 'enable_Ixc_prospecting');
        $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
        $clientGroup = $this->getModuleConfigurationValue($client, 'Ixc_client_group');
        $authButton = $this->getModuleConfigurationValue($client, 'Ixc_auth_button');
        $titleText = $this->getOrCreateModuleConfigurationValue($client, 'Ixc_title_text', "Login via Central do Assinante");
        $subtitleText = $this->getOrCreateModuleConfigurationValue($client, 'Ixc_subtitle_text', "Insira sua credencial de acesso a central do assinante");
        $buttonColor = $this->getOrCreateModuleConfigurationValue($client, 'Ixc_button_color', "#0088cc");
        $credentialsOk = false;

        if ( $clientSecret && $host ) {
            $credentialsOk = $this->IxcService->testCredentials($client);
        }

        $options = [
            'enable_Ixc_integration' => $enableIxc,
            'enable_Ixc_authentication' => $enableIxcAuth,
            'enable_Ixc_prospecting' => $enableIxcProspect,
            'Ixc_client_secret' => $clientSecret,
            'Ixc_host' => $host,
            'Ixc_client_group' => $clientGroup,
            'Ixc_auth_button' => $authButton,
            'Ixc_credentials_ok' => $credentialsOk,
            'Ixc_title_text' => $titleText,
            'Ixc_subtitle_text' => $subtitleText,
            'Ixc_button_color' => $buttonColor
        ];

        $form = $this->controllerHelper->createForm(
            IxcIntegrationType::class,
            null,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $this->updateModuleConfigurationValue($enableIxc, $form->getData()['enable_Ixc_integration']);
            $this->updateModuleConfigurationValue($enableIxcProspect, $form->getData()['enable_Ixc_prospecting']);
            $this->updateModuleConfigurationValue($enableIxcAuth, $form->getData()['enable_Ixc_authentication']);
            $this->updateModuleConfigurationValue($clientSecret, $form->getData()['Ixc_client_secret']);
            // $requiredFields = ["zip_code","number","address","district"];
            // if ($form->getData()['enable_Ixc_prospecting']) {
            //     $this->customFieldsService->checkAndCreateRequiredFields($requiredFields, $client);
            // }
            $this->updateModuleConfigurationValue($host, $form->getData()['Ixc_host']);
            $this->updateModuleConfigurationValue($clientGroup, $form->getData()['Ixc_client_group']);
            $this->updateModuleConfigurationValue($authButton, $form->getData()['Ixc_auth_button']);
            $this->updateModuleConfigurationValue($buttonColor, $form->getData()['Ixc_button_color']);
            $this->updateModuleConfigurationValue($titleText, $form->getData()['Ixc_title_text']);
            $this->updateModuleConfigurationValue($subtitleText, $form->getData()['Ixc_subtitle_text']);

            $this->em->flush();
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('Ixc_integration'));
        }

        return $this->render(
            'AdminBundle:Ixc:form.html.twig',
            [
              'form' => $form->createView(),
            ]
        );
    }

    private function getOrCreateModuleConfigurationValue(Client $client, $key, $defaultValue) {
        $value = $this->em
        ->getRepository('DomainBundle:ModuleConfigurationValue')
        ->findByModuleConfigurationKey($client, $key);

        if ($value) {
            return $value;
        }

        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $client->getId()
        ]);

        $moduleConfiguration = $this->em
            ->getRepository('DomainBundle:ModuleConfiguration')
            ->findOneByKey($key);
        $this->em->persist($client);
        $moduleConfigurationValue = new ModuleConfigurationValue();
        $moduleConfigurationValue->setClient($client);
        $moduleConfigurationValue->setItems($moduleConfiguration);
        $moduleConfigurationValue->setValue($defaultValue);

        $this->em->persist($moduleConfigurationValue);
        $this->em->flush();

        return $moduleConfigurationValue;
    }

    private function getModuleConfigurationValue($client, $key) {
      return $this->em
      ->getRepository('DomainBundle:ModuleConfigurationValue')
      ->findByModuleConfigurationKey($client, $key);
    }

    private function updateModuleConfigurationValue($moduleConfiguration, $value) {
      $moduleConfiguration->setValue($value);
      $this->em->persist($moduleConfiguration);
    }
}