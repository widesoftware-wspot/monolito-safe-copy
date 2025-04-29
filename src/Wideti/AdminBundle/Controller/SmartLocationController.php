<?php

namespace Wideti\AdminBundle\Controller;
use Wideti\DomainBundle\Service\Module\ModuleAware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\SmartLocation\SmartLocationService;


class SmartLocationController
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
	 * @var SmartLocationService
	 */
	private $smartLocationService;


    /**
     * SmartLocationController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
	 * @param SmartLocationService $SmartLocationService
     * @param CacheServiceImp $cacheService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper, SmartLocationService $smartLocationService
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->smartLocationService = $smartLocationService;
        
    }

	/**
	 * @param Request $request
	 * @return Response
	 */
    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('smart_location')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }
        $response = $this->smartLocationService->loginAction();
        if ($response) {
            $jsonString = $response->getBody()->getContents();
            $data = json_decode($jsonString, true);
            if ($data['ok']) {
                return new RedirectResponse($data['url']);
            }
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_dashboard'));
    }
}
