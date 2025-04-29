<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class CacheController
{
    use SecurityAware;
    use TwigAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;

    /**
     * CacheController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     */
	public function __construct(AdminControllerHelper $controllerHelper, CacheServiceImp $cacheService)
    {
        $this->controllerHelper = $controllerHelper;
	    $this->cacheService = $cacheService;
    }

    public function indexAction()
    {
        $user = $this->getUser();

        if (is_null($this->getUser()) || is_null($user->getRole()) || is_null($user->getRole()->getId()) || $user->getRole()->getId() != Users::ROLE_MANAGER) {
            return $this->render('AdminBundle:Cache:permissionDenied.html.twig');
        }

        return $this->render('@Admin/Cache/index.html.twig');
    }

    public function clear(Request $request)
    {
        $type = $request->get('type');

        if ($type == 'all_wspot') {
            $this->cacheService->removeAll();
        } else if ($type == 'all_configs_wspot') {
            $this->cacheService->removeAllConfigs(true);
        } else {
            $this->cacheService->removeCustom(($type == 'all') ? '' : $type);
        }

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('admin_cache')
        );
    }
}
