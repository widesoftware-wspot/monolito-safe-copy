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
use Wideti\DomainBundle\Service\Hubsoft\HubsoftService;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\AdminBundle\Form\HubsoftIntegrationType;



class HubsoftIntegrationController
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
     * @var HubsoftService
     */
    private $hubsoftService;

    /**
     * HubsoftIntegrationController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param HubsoftService $hubsoftService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        HubsoftService $hubsoftService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->hubsoftService       = $hubsoftService;
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
            $clientSecret = $this->getModuleConfigurationValue($client, 'hubsoft_client_secret');
            $clientId = $this->getModuleConfigurationValue($client, 'hubsoft_client_id');
            $username = $this->getModuleConfigurationValue($client, 'hubsoft_username');
            $password = $this->getModuleConfigurationValue($client, 'hubsoft_password');
            $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
            $newClientSecret = $data['client_secret'];
            $newClientId = $data['client_id'];
            $newUsername = $data['username'];
            $newPassword = $data['password'];
            $newHost = $data['host'];
            $this->updateModuleConfigurationValue($clientId, $newClientId);
            $this->updateModuleConfigurationValue($clientSecret, $newClientSecret);
            $this->updateModuleConfigurationValue($username, $newUsername);
            $this->updateModuleConfigurationValue($password, $newPassword);
            $this->updateModuleConfigurationValue($host, $newHost);
            $this->em->flush();
            $credentialsOk = $this->hubsoftService->testCredentials($client);

            if($credentialsOk) {
                return new JsonResponse([
                    "message" => "Credenciais ok!"
                ]);
            } else {
                return new JsonResponse([
                    "message" => "Erro ao validar, cheque as credenciais e tente novamente"
                ], 500); 
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
        if (!$this->moduleService->modulePermission('hubsoft_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $clientSecret = $this->getModuleConfigurationValue($client, 'hubsoft_client_secret');
        $clientId = $this->getModuleConfigurationValue($client, 'hubsoft_client_id');
        $enableHubsoft = $this->getModuleConfigurationValue($client, 'enable_hubsoft_integration');
        $enableHubsoftAuth = $this->getModuleConfigurationValue($client, 'enable_hubsoft_authentication');
        $enableHubsoftProspect = $this->getModuleConfigurationValue($client, 'enable_hubsoft_prospecting');
        $username = $this->getModuleConfigurationValue($client, 'hubsoft_username');
        $password = $this->getModuleConfigurationValue($client, 'hubsoft_password');
        $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
        $clientGroup = $this->getModuleConfigurationValue($client, 'hubsoft_client_group');
        $idService = $this->getModuleConfigurationValue($client, 'hubsoft_id_service');
        $idOrigin = $this->getModuleConfigurationValue($client, 'hubsoft_id_origin');
        $idCrm = $this->getModuleConfigurationValue($client, 'hubsoft_id_crm');
        $authButton = $this->getOrCreateModuleConfigurationValue($client, 'hubsoft_auth_button', "");
        $titleText = $this->getOrCreateModuleConfigurationValue($client, 'hubsoft_title_text', "Login via Hubsoft");
        $subtitleText = $this->getOrCreateModuleConfigurationValue($client, 'hubsoft_subtitle_text', "");
        $buttonColor = $this->getOrCreateModuleConfigurationValue($client, 'hubsoft_button_color', "");
        $credentialsOk = false;
        $originIds = [];
        $crmIds = [];
        $serviceIds = [];
        $passwordLength = strlen($password->getValue());
        $fakePassword = str_repeat('*', $passwordLength);

        if ($clientId && $clientSecret && $host && $username && $password) {
            $credentialsOk = $this->hubsoftService->testCredentials($client);
        }

        if ($credentialsOk) {
            $originIdsResponse = $this->hubsoftService->getIdOrigins($client);
            $serviceIdsResponse = $this->hubsoftService->getIdServices($client);
            if ($originIdsResponse && $originIdsResponse["status"] == "success") {
                $originIdsResponse = $originIdsResponse["origem_clientes"];
                foreach ($originIdsResponse as $origin) {
                    $originIds[$origin["id_origem_cliente"]] = $origin["descricao"];
                }
            }
            if ($serviceIdsResponse && $serviceIdsResponse["status"] == "success") {
                $serviceIdsResponse = $serviceIdsResponse["servicos"];
                foreach ($serviceIdsResponse as $service) {
                    $serviceIds[$service["id_servico"]] = $service["descricao"];
                }
            }
        }

        $options = [
            'enable_hubsoft_integration' => $enableHubsoft,
            'enable_hubsoft_authentication' => $enableHubsoftAuth,
            'enable_hubsoft_prospecting' => $enableHubsoftProspect,
            'hubsoft_client_id' => $clientId,
            'hubsoft_client_secret' => $clientSecret,
            'hubsoft_username' => $username,
            'hubsoft_password' => $password,
            'hubsoft_host' => $host,
            'hubsoft_client_group' => $clientGroup,
            'hubsoft_id_service' => $idService,
            'hubsoft_id_origin' => $idOrigin,
            'hubsoft_id_crm' => $idCrm,
            'hubsoft_auth_button' => $authButton,
            'hubsoft_credentials_ok' => $credentialsOk,
            'origin_ids' => $originIds,
            'service_ids' => $serviceIds,
            'hubsoft_title_text' => $titleText,
            'hubsoft_subtitle_text' => $subtitleText,
            'hubsoft_button_color' => $buttonColor
        ];

        $form = $this->controllerHelper->createForm(
            HubsoftIntegrationType::class,
            null,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $this->updateModuleConfigurationValue($enableHubsoft, $form->getData()['enable_hubsoft_integration']);
            $this->updateModuleConfigurationValue($enableHubsoftProspect, $form->getData()['enable_hubsoft_prospecting']);
            $this->updateModuleConfigurationValue($enableHubsoftAuth, $form->getData()['enable_hubsoft_authentication']);
            $this->updateModuleConfigurationValue($clientId, $form->getData()['hubsoft_client_id']);
            $this->updateModuleConfigurationValue($clientSecret, $form->getData()['hubsoft_client_secret']);
            $this->updateModuleConfigurationValue($username, $form->getData()['hubsoft_username']);
            if (!empty($form->getData()['hubsoft_password']) && $form->getData()['hubsoft_password'] !== $fakePassword ) {
                $this->updateModuleConfigurationValue($password, $form->getData()['hubsoft_password']);
            }

            $requiredFields = ["zip_code","number","address","district"];
            if ($form->getData()['enable_hubsoft_prospecting']) {
                $this->customFieldsService->checkAndCreateRequiredFields($requiredFields, $client);
            }
            $this->updateModuleConfigurationValue($host, $form->getData()['hubsoft_host']);
            $this->updateModuleConfigurationValue($clientGroup, $form->getData()['hubsoft_client_group']);
            $this->updateModuleConfigurationValue($idOrigin, $form->getData()['hubsoft_id_origin']);
            $this->updateModuleConfigurationValue($idService, $form->getData()['hubsoft_id_service']);
            $this->updateModuleConfigurationValue($idCrm, $form->getData()['hubsoft_id_crm']);
            $this->updateModuleConfigurationValue($authButton, $form->getData()['hubsoft_auth_button']);
            $this->updateModuleConfigurationValue($buttonColor, $form->getData()['hubsoft_button_color']);
            $this->updateModuleConfigurationValue($titleText, $form->getData()['hubsoft_title_text']);
            $this->updateModuleConfigurationValue($subtitleText, $form->getData()['hubsoft_subtitle_text']);

            $this->em->flush();
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('hubsoft_integration'));
        }

        return $this->render(
            'AdminBundle:Hubsoft:form.html.twig',
            [
              'form' => $form->createView(),
              'fakePassword' => $fakePassword
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