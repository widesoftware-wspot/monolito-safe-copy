<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\DomainBundle\Service\Module\ModuleAware;

use Symfony\Component\HttpFoundation\Request;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\AdminBundle\Form\DeskbeeIntegrationType;


class DeskbeeIntegrationController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    /**
     * BusinessHoursController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper
    ) {
        $this->controllerHelper     = $controllerHelper;
    }


	/**
	 * @param Request $request
	 * @return Response
	 */
    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('deskbee_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $clientSecret = $this->getModuleConfigurationValue($client, 'deskbee_client_secret');
        $clientId = $this->getModuleConfigurationValue($client, 'deskbee_client_id');
        $enableDeskbee = $this->getModuleConfigurationValue($client, 'enable_deskbee_integration');
        $redirectUrl = $this->getModuleConfigurationValue($client, 'deskbee_redirect_url');
        $deskbeeEnv = $this->getModuleConfigurationValue($client, 'deskbee_environment');

        $options = [
            'enable_deskbee_integration' => $enableDeskbee,
            'deskbee_client_id' => $clientId,
            'deskbee_client_secret' => $clientSecret,
            'deskbee_redirect_url' => $redirectUrl,
            'deskbee_environment' => $deskbeeEnv
        ];

        $form = $this->controllerHelper->createForm(
            DeskbeeIntegrationType::class,
            null,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $this->updateModuleConfigurationValue($enableDeskbee, $form->getData()['enable_deskbee_integration']);
            $this->updateModuleConfigurationValue($clientId, $form->getData()['deskbee_client_id']);
            $this->updateModuleConfigurationValue($clientSecret, $form->getData()['deskbee_client_secret']);
            $this->updateModuleConfigurationValue($redirectUrl, $form->getData()['deskbee_redirect_url']);
            $this->updateModuleConfigurationValue($deskbeeEnv, $form->getData()['deskbee_environment']);

            $this->em->flush();
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('deskbee_integration'));
        }

        return $this->render(
            'AdminBundle:Deskbee:form.html.twig',
            [
              'form' => $form->createView()
            ]
        );
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